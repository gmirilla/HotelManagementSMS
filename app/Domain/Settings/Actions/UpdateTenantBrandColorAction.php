<?php

declare(strict_types=1);

namespace App\Domain\Settings\Actions;

use App\Models\Tenant;
use App\Support\Theme\BrandPalette;
use Illuminate\Validation\ValidationException;

/**
 * The sole write path for a tenant's brand color. A plain attribute update
 * rather than a bigger "Settings" aggregate because brand color is currently
 * the only tenant-level appearance setting — see App\Support\Theme\BrandPalette
 * for how the stored hex is turned into the actual CSS ramp at render time.
 */
class UpdateTenantBrandColorAction
{
    public function handle(Tenant $tenant, string $hexColor): Tenant
    {
        if (! BrandPalette::isValidHex($hexColor)) {
            throw ValidationException::withMessages([
                'brand_color' => __('Enter a valid hex color, e.g. #4f46e5.'),
            ]);
        }

        $tenant->update(['brand_color' => $hexColor]);

        return $tenant;
    }
}
