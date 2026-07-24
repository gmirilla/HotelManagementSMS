<?php

declare(strict_types=1);

namespace App\Domain\Branch\Actions;

use App\Models\Branch;
use App\Models\Tenant;

/**
 * The sole write path for adding a branch to a tenant. A brand-new branch
 * always starts active — BranchManager immediately shows it in every
 * branch-scoped list and switcher, which is the point of adding one.
 */
class CreateBranchAction
{
    public function handle(
        Tenant $tenant,
        string $name,
        string $code,
        string $currency,
        string $timezone,
        ?string $addressLine1,
        ?string $city,
        ?string $country,
        string $checkInTime,
        string $checkOutTime,
    ): Branch {
        return Branch::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'code' => $code,
            'currency' => $currency,
            'timezone' => $timezone,
            'address_line1' => $addressLine1,
            'city' => $city,
            'country' => $country,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'is_active' => true,
        ]);
    }
}
