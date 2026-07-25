<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Payment\Actions\RefundGatewayPaymentAction;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

function makeCompletedGatewayPayment(int $amountCents = 20000): Payment
{
    $tenant = Tenant::factory()->create(['paystack_secret_key' => 'sk_test_fake']);
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $folio = Folio::factory()->create(['branch_id' => $branch->id, 'status' => FolioStatus::Open]);
    $folio->charges()->create(['charge_type' => ChargeType::Room, 'description' => 'Room charge', 'amount_cents' => $amountCents, 'charge_date' => now()->toDateString()]);

    $payment = Payment::factory()->create([
        'branch_id' => $branch->id,
        'folio_id' => $folio->id,
        'method' => PaymentMethod::Paystack,
        'status' => PaymentStatus::Completed,
        'amount_cents' => $amountCents,
        'gateway_reference' => 'refund-me',
    ]);

    app(FolioBalanceCalculator::class)->recalculate($folio);

    return $payment;
}

test('a successful refund flips the payment to Refunded and reopens the folio balance', function (): void {
    Http::fake([
        'api.paystack.co/refund' => Http::response(['status' => true, 'data' => ['status' => 'pending']], 200),
    ]);

    $payment = makeCompletedGatewayPayment(20000);
    expect($payment->folio->fresh()->balance_cents)->toBe(0);

    $refunded = app(RefundGatewayPaymentAction::class)->handle($payment, 'guest requested');

    expect($refunded->status)->toBe(PaymentStatus::Refunded)
        ->and($refunded->refund_reason)->toBe('guest requested')
        ->and($payment->folio->fresh()->balance_cents)->toBe(20000);
});

test('only a completed payment can be refunded', function (): void {
    $payment = makeCompletedGatewayPayment();
    $payment->update(['status' => PaymentStatus::Pending]);

    app(RefundGatewayPaymentAction::class)->handle($payment, null);
})->throws(ValidationException::class);

test('a manual (non-gateway) payment cannot be refunded through this action', function (): void {
    $payment = makeCompletedGatewayPayment();
    $payment->update(['method' => PaymentMethod::Cash]);

    app(RefundGatewayPaymentAction::class)->handle($payment, null);
})->throws(ValidationException::class);

test('a gateway-rejected refund throws and leaves the payment Completed', function (): void {
    Http::fake([
        'api.paystack.co/refund' => Http::response(['status' => false, 'message' => 'Transaction not refundable'], 400),
    ]);

    $payment = makeCompletedGatewayPayment();

    try {
        app(RefundGatewayPaymentAction::class)->handle($payment, null);
    } catch (Throwable) {
        // expected — ->throw() on the non-2xx gateway response
    }

    expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);
});
