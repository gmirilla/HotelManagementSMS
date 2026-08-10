<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateKitchenItemStatusAction
{
    public function handle(RestaurantOrderItem $item, KitchenStatus $status): RestaurantOrderItem
    {
        if ($item->order->status !== OrderStatus::SentToKitchen) {
            throw ValidationException::withMessages(['order' => __('Kitchen status can only be updated while the order is with the kitchen.')]);
        }

        if (! $item->kitchen_status->canAdvanceTo($status)) {
            throw ValidationException::withMessages(['status' => __('Kitchen status cannot move backward or skip a step.')]);
        }

        return DB::transaction(function () use ($item, $status) {
            $item->update(['kitchen_status' => $status]);

            if ($status === KitchenStatus::Served) {
                // Locked so two items on the same order being served in the
                // same instant can't both read "not everything's served
                // yet" before either commits, leaving the order stuck at
                // SentToKitchen even though every item is actually Served.
                $order = RestaurantOrder::whereKey($item->order_id)->lockForUpdate()->firstOrFail();

                if ($order->status === OrderStatus::SentToKitchen) {
                    $allServed = $order->items()->get()->every(fn (RestaurantOrderItem $line) => $line->kitchen_status === KitchenStatus::Served);

                    if ($allServed) {
                        $order->update(['status' => OrderStatus::Served]);
                    }
                }
            }

            return $item;
        });
    }
}
