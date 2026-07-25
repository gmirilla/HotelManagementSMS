<?php

declare(strict_types=1);

namespace App\Domain\Settings\Actions;

use App\Models\Tenant;

/**
 * The sole write path for a tenant's Paystack credentials. The secret key
 * is write-only from the UI's perspective (PaymentSettings never redisplays
 * the decrypted value) — a blank $secretKey here means "leave the stored
 * key unchanged", not "clear it"; there's no clear-key affordance in v1.
 */
class UpdatePaymentSettingsAction
{
    public function handle(Tenant $tenant, ?string $publicKey, ?string $secretKey): Tenant
    {
        $attributes = [
            'paystack_public_key' => $publicKey,
        ];

        if ($secretKey !== null && $secretKey !== '') {
            $attributes['paystack_secret_key'] = $secretKey;
        }

        $tenant->update($attributes);

        return $tenant;
    }
}
