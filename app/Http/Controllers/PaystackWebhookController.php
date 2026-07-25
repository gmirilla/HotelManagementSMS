<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Payment\Actions\ConfirmGatewayPaymentAction;
use App\Domain\Payment\Gateways\PaystackGateway;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Paystack's servers call this with no session of any kind — it lives
 * outside auth/CSRF entirely (see routes/api/v1.php). Credentials are
 * per-tenant, so the signature can only be checked *after* working out
 * which tenant a payload belongs to: look the reference up first, resolve
 * its tenant, verify with that tenant's own secret, and only then act. The
 * webhook is treated as a nudge to re-check, never as the source of truth
 * by itself — ConfirmGatewayPaymentAction always re-verifies against
 * Paystack directly before completing anything.
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, ConfirmGatewayPaymentAction $confirm): Response
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);

        $reference = $payload['data']['reference'] ?? null;

        if (! is_string($reference) || $reference === '') {
            return response()->noContent(400);
        }

        $payment = Payment::where('gateway_reference', $reference)->first();

        if (! $payment) {
            return response()->noContent(404);
        }

        $tenant = $payment->branch->tenant;
        $secretKey = $tenant->paystack_secret_key;

        if ($secretKey === null || ! PaystackGateway::verifyWebhookSignature($rawBody, $request->header('x-paystack-signature', ''), $secretKey)) {
            return response()->noContent(403);
        }

        if (($payload['event'] ?? null) === 'charge.success') {
            $confirm->handle($reference);
        }

        return response()->noContent();
    }
}
