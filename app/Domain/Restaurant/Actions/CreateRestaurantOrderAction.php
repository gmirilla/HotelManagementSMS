<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\OrderType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateRestaurantOrderAction
{
    public function handle(
        int $branchId,
        int $outletId,
        User $staff,
        OrderType $orderType,
        ?RestaurantTable $table = null,
        ?int $guestId = null,
    ): RestaurantOrder {
        if ($orderType === OrderType::DineIn && ! $table) {
            throw ValidationException::withMessages(['table' => __('A dine-in order requires a table.')]);
        }

        return DB::transaction(function () use ($branchId, $outletId, $staff, $orderType, $table, $guestId) {
            // Locked so two concurrent requests for the same table can't
            // both pass the "is seatable" check before either commits and
            // open duplicate orders against it. A Reserved table can still
            // be seated — that's the point of a reservation — only an
            // already-Occupied table is rejected.
            $lockedTable = $table instanceof RestaurantTable ? RestaurantTable::whereKey($table->id)->lockForUpdate()->firstOrFail() : null;

            if ($lockedTable && ! in_array($lockedTable->status, [TableStatus::Free, TableStatus::Reserved], true)) {
                throw ValidationException::withMessages(['table' => __('This table is not free.')]);
            }

            $order = RestaurantOrder::create([
                'branch_id' => $branchId,
                'outlet_id' => $outletId,
                'table_id' => $lockedTable?->id,
                'guest_id' => $guestId,
                'order_type' => $orderType,
                'status' => OrderStatus::Open,
                'opened_by_user_id' => $staff->id,
            ]);

            $lockedTable?->update(['status' => TableStatus::Occupied]);

            return $order;
        });
    }
}
