<?php

declare(strict_types=1);

namespace App\Domain\Settings\Actions;

use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The sole write path for a tenant's organization-level details: name,
 * default currency/timezone (branches can still override their own — see
 * Branch::currency/timezone — these are just what a new branch starts
 * from), and logo. Kept separate from UpdateTenantBrandColorAction since
 * that one is deliberately a single-field, high-frequency "try a color"
 * write path; this one is the infrequent "organization details" form.
 */
class UpdateHotelSettingsAction
{
    public function handle(
        Tenant $tenant,
        string $name,
        string $defaultCurrency,
        string $defaultTimezone,
        ?UploadedFile $logo = null,
    ): Tenant {
        $attributes = [
            'name' => $name,
            'default_currency' => $defaultCurrency,
            'default_timezone' => $defaultTimezone,
        ];

        if ($logo !== null) {
            if ($tenant->logo_path !== null) {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            $attributes['logo_path'] = $logo->store('tenant-logos', 'public');
        }

        $tenant->update($attributes);

        return $tenant;
    }
}
