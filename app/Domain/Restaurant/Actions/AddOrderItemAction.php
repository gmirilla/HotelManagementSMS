<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Support\OrderTotalCalculator;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddOrderItemAction
{
    public function __construct(private readonly OrderTotalCalculator $totalCalculator) {}

    public function handle(RestaurantOrder $order, MenuItem $menuItem, int $quantity): RestaurantOrderItem
    {
        if ($order->status !== OrderStatus::Open) {
            throw ValidationException::withMessages(['order' => __('Items can only be added to an open order.')]);
        }

        if (! $menuItem->is_available) {
            throw ValidationException::withMessages(['menu_item' => __('This menu item is currently unavailable.')]);
        }

        return DB::transaction(function () use ($order, $menuItem, $quantity) {
            $item = $order->items()->create([
                'menu_item_id' => $menuItem->id,
                'quantity' => $quantity,
                'unit_price_cents' => $menuItem->price_cents,
                'kitchen_status' => KitchenStatus::Queued,
            ]);

            $order->refresh();
            $this->totalCalculator->recalculate($order);

            return $item;
        });
    }
}
