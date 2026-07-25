<?php

declare(strict_types=1);

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\DataTransferObjects\GatewayInitiationResult;
use App\Domain\Payment\DataTransferObjects\GatewayRefundResult;
use App\Domain\Payment\DataTransferObjects\GatewayVerificationResult;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to Paystack's hosted-checkout API only — initialize/verify/refund
 * exchange a reference and an amount, never card data. Each tenant runs
 * this against their own Paystack merchant account (Tenant::paystack_secret_key),
 * not a shared platform account, so money settles directly to the hotel
 * group, not through this application.
 */
class PaystackGateway implements PaymentGateway
{
    private const string BASE_URL = 'https://api.paystack.co';

    public function __construct(private readonly string $secretKey) {}

    public static function forTenant(Tenant $tenant): self
    {
        if ($tenant->paystack_secret_key === null || $tenant->paystack_secret_key === '') {
            throw new RuntimeException('This hotel has not configured a Paystack secret key yet.');
        }

        return new self($tenant->paystack_secret_key);
    }

    public function initialize(Payment $payment, string $email, string $callbackUrl): GatewayInitiationResult
    {
        $response = $this->client()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $payment->amount_cents,
            'currency' => $payment->currency,
            'reference' => $payment->gateway_reference,
            'callback_url' => $callbackUrl,
        ])->throw();

        $data = $response->json('data');

        return new GatewayInitiationResult(
            authorizationUrl: $data['authorization_url'],
            reference: $data['reference'],
        );
    }

    public function verifyTransaction(string $reference): GatewayVerificationResult
    {
        $response = $this->client()->get("/transaction/verify/{$reference}")->throw();

        $data = $response->json('data');
        $status = $data['status'] ?? 'unknown';

        return new GatewayVerificationResult(
            successful: $status === 'success',
            reference: $data['reference'] ?? $reference,
            amountCents: (int) ($data['amount'] ?? 0),
            currency: $data['currency'] ?? '',
            rawStatus: $status,
        );
    }

    public function refund(Payment $payment, ?string $reason): GatewayRefundResult
    {
        $response = $this->client()->post('/refund', array_filter([
            'transaction' => $payment->gateway_reference,
            'merchant_note' => $reason,
        ]))->throw();

        $status = $response->json('data.status') ?? 'unknown';

        return new GatewayRefundResult(
            successful: in_array($status, ['pending', 'processed', 'success'], true),
            rawStatus: $status,
        );
    }

    /**
     * Deliberately a pure function (no HTTP, no gateway instance needed) so
     * it's testable without any mocking — this is the one piece of webhook
     * handling that must never be wrong.
     */
    public static function verifyWebhookSignature(string $rawBody, string $signature, string $secretKey): bool
    {
        if ($signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $rawBody, $secretKey), $signature);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)->withToken($this->secretKey)->acceptJson();
    }
}
