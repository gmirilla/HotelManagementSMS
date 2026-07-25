<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Payment\Actions\ConfirmGatewayPaymentAction;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;

function makePendingGatewayPayment(int $amountCents = 20000): Payment
{
    $tenant = Tenant::factory()->create(['paystack_secret_key' => 'sk_test_fake']);
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    seedChartOfAccounts($branch);
    $folio = Folio::factory()->create(['branch_id' => $branch->id, 'status' => FolioStatus::Open]);
    $folio->charges()->create(['charge_type' => ChargeType::Room, 'description' => 'Room charge', 'amount_cents' => $amountCents, 'charge_date' => now()->toDateString()]);
    app(FolioBalanceCalculator::class)->recalculate($folio);

    return Payment::factory()->create([
        'branch_id' => $branch->id,
        'folio_id' => $folio->id,
        'method' => PaymentMethod::Paystack,
        'status' => PaymentStatus::Pending,
        'amount_cents' => $amountCents,
        'gateway_reference' => 'confirm-me',
    ]);
}

test('a successful verification completes the payment and reduces the folio balance', function (): void {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'success', 'reference' => 'confirm-me', 'amount' => 20000, 'currency' => 'NGN'],
        ], 200),
    ]);

    $payment = makePendingGatewayPayment(20000);
    expect($payment->folio->fresh()->balance_cents)->toBe(20000);

    $confirmed = app(ConfirmGatewayPaymentAction::class)->handle('confirm-me');

    expect($confirmed->status)->toBe(PaymentStatus::Completed)
        ->and($confirmed->paid_at)->not->toBeNull()
        ->and($payment->folio->fresh()->balance_cents)->toBe(0);

    $entry = JournalEntry::where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->firstOrFail();
    expect($entry->isBalanced())->toBeTrue()
        ->and($entry->totalDebitCents())->toBe(20000);
});

test('confirming twice is idempotent — only one verify call, only one balance change', function (): void {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'success', 'reference' => 'confirm-me', 'amount' => 20000, 'currency' => 'NGN'],
        ], 200),
    ]);

    $payment = makePendingGatewayPayment(20000);

    app(ConfirmGatewayPaymentAction::class)->handle('confirm-me');
    $secondResult = app(ConfirmGatewayPaymentAction::class)->handle('confirm-me');

    expect($secondResult->status)->toBe(PaymentStatus::Completed)
        ->and($payment->folio->fresh()->balance_cents)->toBe(0);

    Http::assertSentCount(1);
    expect(JournalEntry::where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->count())->toBe(1);
});

test('an amount mismatch is treated as a failure, not accepted', function (): void {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'success', 'reference' => 'confirm-me', 'amount' => 999, 'currency' => 'NGN'],
        ], 200),
    ]);

    $payment = makePendingGatewayPayment(20000);

    $result = app(ConfirmGatewayPaymentAction::class)->handle('confirm-me');

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($payment->folio->fresh()->balance_cents)->toBe(20000);
});

test('an unsuccessful verification marks the payment Failed', function (): void {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'abandoned', 'reference' => 'confirm-me', 'amount' => 20000, 'currency' => 'NGN'],
        ], 200),
    ]);

    $payment = makePendingGatewayPayment(20000);

    $result = app(ConfirmGatewayPaymentAction::class)->handle('confirm-me');

    expect($result->status)->toBe(PaymentStatus::Failed);
});
