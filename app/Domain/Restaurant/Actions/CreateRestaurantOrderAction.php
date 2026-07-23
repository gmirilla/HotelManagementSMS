<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\OrderType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\User;
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

        if ($table && $table->status !== TableStatus::Free) {
            throw ValidationException::withMessages(['table' => __('This table is not free.')]);
        }

        $order = RestaurantOrder::create([
            'branch_id' => $branchId,
            'outlet_id' => $outletId,
            'table_id' => $table?->id,
            'guest_id' => $guestId,
            'order_type' => $orderType,
            'status' => OrderStatus::Open,
            'opened_by_user_id' => $staff->id,
        ]);

        $table?->update(['status' => TableStatus::Occupied]);

        return $order;
    }
}
