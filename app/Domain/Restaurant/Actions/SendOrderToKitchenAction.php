<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Enums\OrderStatus;
use App\Models\RestaurantOrder;
use Illuminate\Validation\ValidationException;

class SendOrderToKitchenAction
{
    public function handle(RestaurantOrder $order): RestaurantOrder
    {
        if ($order->status !== OrderStatus::Open || $order->items->isEmpty()) {
            throw ValidationException::withMessages(['order' => __('An order needs at least one item before it can be sent to the kitchen.')]);
        }

        $order->update(['status' => OrderStatus::SentToKitchen]);

        return $order;
    }
}
