<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\Payment\Actions\InitiateGatewayPaymentAction;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

function makeFolioWithGateway(?string $email = 'guest@example.com'): Folio
{
    $tenant = Tenant::factory()->create(['paystack_secret_key' => 'sk_test_fake']);
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id, 'currency' => 'NGN']);
    $guest = Guest::factory()->create(['tenant_id' => $tenant->id, 'email' => $email]);

    return Folio::factory()->create(['branch_id' => $branch->id, 'guest_id' => $guest->id, 'status' => FolioStatus::Open]);
}

test('initiating a payment creates a Pending payment and returns the checkout URL', function (): void {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz', 'access_code' => 'xyz', 'reference' => 'will-be-overridden'],
        ], 200),
    ]);

    $folio = makeFolioWithGateway();
    $staff = User::factory()->create();

    $result = app(InitiateGatewayPaymentAction::class)->handle($folio, 20000, 'https://example.test/callback', $staff);

    expect($result->authorizationUrl)->toBe('https://checkout.paystack.com/xyz');

    $payment = Payment::where('folio_id', $folio->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->method)->toBe(PaymentMethod::Paystack)
        ->and($payment->amount_cents)->toBe(20000)
        ->and($payment->currency)->toBe('NGN')
        ->and($payment->gateway_reference)->not->toBeNull();
});

test('a zero or negative amount is rejected', function (): void {
    $folio = makeFolioWithGateway();
    $staff = User::factory()->create();

    app(InitiateGatewayPaymentAction::class)->handle($folio, 0, 'https://example.test/callback', $staff);
})->throws(ValidationException::class);

test('a closed folio cannot accept a payment', function (): void {
    $folio = makeFolioWithGateway();
    $folio->update(['status' => FolioStatus::Closed]);
    $staff = User::factory()->create();

    app(InitiateGatewayPaymentAction::class)->handle($folio, 5000, 'https://example.test/callback', $staff);
})->throws(ValidationException::class);

test('a guest with no email on file cannot pay online', function (): void {
    $folio = makeFolioWithGateway(email: null);
    $staff = User::factory()->create();

    app(InitiateGatewayPaymentAction::class)->handle($folio, 5000, 'https://example.test/callback', $staff);
})->throws(ValidationException::class);

test('a gateway failure flips the just-created payment to Failed instead of leaving it Pending forever', function (): void {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response(['status' => false, 'message' => 'Bad request'], 400),
    ]);

    $folio = makeFolioWithGateway();
    $staff = User::factory()->create();

    try {
        app(InitiateGatewayPaymentAction::class)->handle($folio, 5000, 'https://example.test/callback', $staff);
    } catch (Throwable) {
        // expected — the HTTP client throws on a non-2xx response via ->throw()
    }

    $payment = Payment::where('folio_id', $folio->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Failed);
});
