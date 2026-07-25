<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\DataTransferObjects\GatewayInitiationResult;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Gateways\PaystackGateway;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Starts a hosted-checkout payment: creates a Pending Payment row up front
 * (with our own reference, so there's always something to reconcile against
 * even if the outbound call to Paystack itself fails) and returns the URL
 * to send the browser to. Never completes a payment itself — that only
 * happens through ConfirmGatewayPaymentAction, driven by a verified
 * server-to-server check, not by anything this method returns.
 */
class InitiateGatewayPaymentAction
{
    public function handle(Folio $folio, int $amountCents, string $callbackUrl, User $staff): GatewayInitiationResult
    {
        if (! $folio->isOpen()) {
            throw ValidationException::withMessages(['folio' => __('Only an open folio can accept a payment.')]);
        }

        if ($amountCents <= 0) {
            throw ValidationException::withMessages(['amount' => __('Payment amount must be greater than zero.')]);
        }

        $email = $folio->guest?->email;

        if ($email === null || $email === '') {
            throw ValidationException::withMessages(['email' => __('This guest has no email on file — one is required to pay online.')]);
        }

        $payment = Payment::create([
            'branch_id' => $folio->branch_id,
            'folio_id' => $folio->id,
            'method' => PaymentMethod::Paystack,
            'amount_cents' => $amountCents,
            'currency' => $folio->branch->currency,
            'status' => PaymentStatus::Pending,
            'gateway_reference' => (string) Str::uuid(),
            'received_by_user_id' => $staff->id,
        ]);

        try {
            $gateway = PaystackGateway::forTenant($folio->branch->tenant);

            return $gateway->initialize($payment, $email, $callbackUrl);
        } catch (Throwable $exception) {
            $payment->update(['status' => PaymentStatus::Failed]);

            // Surface the gateway's own reason where there is one (e.g. "Currency
            // not supported by merchant") — this is shown to staff, not the
            // guest, so a specific, actionable message beats a generic one.
            $gatewayMessage = $exception instanceof RequestException
                ? $exception->response->json('message')
                : null;

            throw ValidationException::withMessages([
                'payment' => $gatewayMessage ?? __('Could not start the online payment. Please try again or use another payment method.'),
            ]);
        }
    }
}
