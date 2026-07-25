<?php

declare(strict_types=1);

use App\Domain\Payment\Gateways\PaystackGateway;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;

test('forTenant throws when the tenant has no secret key configured', function (): void {
    $tenant = Tenant::factory()->create(['paystack_secret_key' => null]);

    PaystackGateway::forTenant($tenant);
})->throws(RuntimeException::class);

test('forTenant succeeds once a secret key is set', function (): void {
    $tenant = Tenant::factory()->create(['paystack_secret_key' => 'sk_test_fake']);

    expect(PaystackGateway::forTenant($tenant))->toBeInstanceOf(PaystackGateway::class);
});

test('initialize posts the payment reference and amount, and returns the checkout URL', function (): void {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/abc123',
                'access_code' => 'abc123',
                'reference' => 'my-reference',
            ],
        ], 200),
    ]);

    $payment = Payment::factory()->create(['amount_cents' => 50000, 'currency' => 'NGN', 'gateway_reference' => 'my-reference']);
    $gateway = new PaystackGateway('sk_test_fake');

    $result = $gateway->initialize($payment, 'guest@example.com', 'https://example.test/callback');

    expect($result->authorizationUrl)->toBe('https://checkout.paystack.com/abc123')
        ->and($result->reference)->toBe('my-reference');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.paystack.co/transaction/initialize'
        && $request->hasHeader('Authorization', 'Bearer sk_test_fake')
        && $request['email'] === 'guest@example.com'
        && $request['amount'] === 50000
        && $request['currency'] === 'NGN'
        && $request['reference'] === 'my-reference');
});

test('verifyTransaction reports success only when Paystack says the charge succeeded', function (): void {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'success', 'reference' => 'my-reference', 'amount' => 50000, 'currency' => 'NGN'],
        ], 200),
    ]);

    $gateway = new PaystackGateway('sk_test_fake');
    $result = $gateway->verifyTransaction('my-reference');

    expect($result->successful)->toBeTrue()
        ->and($result->amountCents)->toBe(50000)
        ->and($result->currency)->toBe('NGN');
});

test('verifyTransaction reports failure for an abandoned or failed transaction', function (): void {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'abandoned', 'reference' => 'my-reference', 'amount' => 50000, 'currency' => 'NGN'],
        ], 200),
    ]);

    $gateway = new PaystackGateway('sk_test_fake');
    $result = $gateway->verifyTransaction('my-reference');

    expect($result->successful)->toBeFalse();
});

test('refund reports success for a queued or processed refund', function (): void {
    Http::fake([
        'api.paystack.co/refund' => Http::response([
            'status' => true,
            'data' => ['transaction_reference' => 'my-reference', 'status' => 'pending'],
        ], 200),
    ]);

    $payment = Payment::factory()->create(['gateway_reference' => 'my-reference']);
    $gateway = new PaystackGateway('sk_test_fake');

    $result = $gateway->refund($payment, 'guest requested');

    expect($result->successful)->toBeTrue()
        ->and($result->rawStatus)->toBe('pending');
});

test('verifyWebhookSignature accepts a correctly-signed body and rejects a tampered one', function (): void {
    $secret = 'sk_test_fake';
    $body = '{"event":"charge.success","data":{"reference":"my-reference"}}';
    $validSignature = hash_hmac('sha512', $body, $secret);

    expect(PaystackGateway::verifyWebhookSignature($body, $validSignature, $secret))->toBeTrue()
        ->and(PaystackGateway::verifyWebhookSignature($body, 'not-the-right-signature', $secret))->toBeFalse()
        ->and(PaystackGateway::verifyWebhookSignature($body, '', $secret))->toBeFalse()
        ->and(PaystackGateway::verifyWebhookSignature($body . 'tampered', $validSignature, $secret))->toBeFalse();
});
