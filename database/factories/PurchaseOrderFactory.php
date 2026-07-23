<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'supplier_id' => Supplier::factory(),
            'created_by_user_id' => User::factory(),
            'po_number' => 'PO-' . Str::upper(Str::random(6)),
            'status' => PurchaseOrderStatus::Draft,
            'total_cents' => 0,
        ];
    }
}
