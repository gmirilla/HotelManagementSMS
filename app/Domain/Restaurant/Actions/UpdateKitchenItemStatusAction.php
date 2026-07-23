<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Models\RestaurantOrderItem;

class UpdateKitchenItemStatusAction
{
    public function handle(RestaurantOrderItem $item, KitchenStatus $status): RestaurantOrderItem
    {
        $item->update(['kitchen_status' => $status]);

        $order = $item->order;

        if ($status === KitchenStatus::Served && $order->status === OrderStatus::SentToKitchen) {
            $allServed = $order->items()->get()->every(fn (RestaurantOrderItem $line) => $line->kitchen_status === KitchenStatus::Served);

            if ($allServed) {
                $order->update(['status' => OrderStatus::Served]);
            }
        }

        return $item;
    }
}
