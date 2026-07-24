<?php

declare(strict_types=1);

namespace App\Domain\Branch\Actions;

use App\Models\Branch;

/**
 * The sole write path for editing a branch's own details. Active/inactive
 * status is deliberately not part of this — see SetBranchActiveStatusAction
 * — toggling whether a branch is open for business is a distinct, more
 * consequential action than correcting its address or check-in time.
 */
class UpdateBranchAction
{
    public function handle(
        Branch $branch,
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
        $branch->update([
            'name' => $name,
            'code' => $code,
            'currency' => $currency,
            'timezone' => $timezone,
            'address_line1' => $addressLine1,
            'city' => $city,
            'country' => $country,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
        ]);

        return $branch;
    }
}
