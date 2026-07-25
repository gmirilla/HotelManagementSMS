<?php

declare(strict_types=1);

namespace App\Domain\Branch\Actions;

use App\Domain\Accounting\Actions\SeedDefaultChartOfAccountsAction;
use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * The sole write path for adding a branch to a tenant. A brand-new branch
 * always starts active — BranchManager immediately shows it in every
 * branch-scoped list and switcher, which is the point of adding one. It
 * also always starts with a working chart of accounts (FR-ACC-001) — without
 * one, the first folio charge or payment at this branch would fail with a
 * missing-account error instead of posting.
 */
class CreateBranchAction
{
    public function __construct(private readonly SeedDefaultChartOfAccountsAction $seedChartOfAccounts) {}

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
        return DB::transaction(function () use ($tenant, $name, $code, $currency, $timezone, $addressLine1, $city, $country, $checkInTime, $checkOutTime) {
            $branch = Branch::create([
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

            $this->seedChartOfAccounts->handle($branch);

            return $branch;
        });
    }
}
