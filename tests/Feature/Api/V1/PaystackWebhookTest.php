<?php

declare(strict_types=1);

use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;

function makeWebhookPayment(string $secretKey = 'sk_test_fake'): Payment
{
    $tenant = Tenant::factory()->create(['paystack_secret_key' => $secretKey]);
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    seedChartOfAccounts($branch);

    return Payment::factory()->create([
        'branch_id' => $branch->id,
        'method' => PaymentMethod::Paystack,
        'status' => PaymentStatus::Pending,
        'amount_cents' => 20000,
        'gateway_reference' => 'webhook-reference',
    ]);
}

function fakeVerifySuccess(int $amountCents = 20000): void
{
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'success', 'reference' => 'webhook-reference', 'amount' => $amountCents, 'currency' => 'NGN'],
        ], 200),
    ]);
}

test('a correctly-signed charge.success webhook confirms the payment', function (): void {
    $payment = makeWebhookPayment();
    fakeVerifySuccess();

    $payload = ['event' => 'charge.success', 'data' => ['reference' => 'webhook-reference']];
    $signature = hash_hmac('sha512', json_encode($payload), 'sk_test_fake');

    $this->postJson('/api/v1/webhooks/paystack', $payload, ['X-Paystack-Signature' => $signature])
        ->assertNoContent();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);
});

test('an incorrectly-signed webhook is rejected and never confirms the payment', function (): void {
    $payment = makeWebhookPayment();
    fakeVerifySuccess();

    $payload = ['event' => 'charge.success', 'data' => ['reference' => 'webhook-reference']];

    $this->postJson('/api/v1/webhooks/paystack', $payload, ['X-Paystack-Signature' => 'not-the-right-signature'])
        ->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('a webhook for an unknown reference is ignored, not an error that leaks information', function (): void {
    $payload = ['event' => 'charge.success', 'data' => ['reference' => 'no-such-reference']];
    $signature = hash_hmac('sha512', json_encode($payload), 'irrelevant-since-payment-not-found');

    $this->postJson('/api/v1/webhooks/paystack', $payload, ['X-Paystack-Signature' => $signature])
        ->assertNotFound();
});

test('a replayed webhook stays idempotent — one verify call, one completion', function (): void {
    $payment = makeWebhookPayment();
    fakeVerifySuccess();

    $payload = ['event' => 'charge.success', 'data' => ['reference' => 'webhook-reference']];
    $signature = hash_hmac('sha512', json_encode($payload), 'sk_test_fake');

    $this->postJson('/api/v1/webhooks/paystack', $payload, ['X-Paystack-Signature' => $signature])->assertNoContent();
    $this->postJson('/api/v1/webhooks/paystack', $payload, ['X-Paystack-Signature' => $signature])->assertNoContent();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);
    Http::assertSentCount(1);
});
