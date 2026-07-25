<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Accounting\Support\RestaurantLedgerPoster;
use App\Domain\FrontDesk\Actions\PostFolioChargeAction;
use App\Domain\Inventory\Actions\IssueStockAction;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Models\Folio;
use App\Models\MenuItemIngredient;
use App\Models\RestaurantOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Closing an order is the point ingredients leave inventory (FR-POS-006) —
 * not when the kitchen marks an item "served" — since a served-but-later-voided
 * item shouldn't have already decremented stock. For room-service orders tied
 * to a guest with an open folio, the total is posted there instead of settled
 * at the outlet (FR-POS-005); every other order (dine-in, takeaway) has
 * nowhere else to settle, so it posts as a direct cash sale instead (FR-ACC-002
 * — every revenue-affecting transaction generates a journal entry, folio or not).
 */
class CloseRestaurantOrderAction
{
    public function __construct(
        private readonly IssueStockAction $issueStock,
        private readonly PostFolioChargeAction $postFolioCharge,
        private readonly RestaurantLedgerPoster $restaurantLedger,
    ) {}

    public function handle(RestaurantOrder $order, User $staff): RestaurantOrder
    {
        if (! in_array($order->status, [OrderStatus::Open, OrderStatus::SentToKitchen, OrderStatus::Served], true)) {
            throw ValidationException::withMessages(['status' => __('This order cannot be closed from its current status.')]);
        }

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages(['order' => __('An order with no items cannot be closed.')]);
        }

        return DB::transaction(function () use ($order, $staff) {
            $this->deductIngredients($order, $staff);

            $folio = $this->resolveOpenFolioForGuest($order);

            if ($folio instanceof Folio) {
                $this->postFolioCharge->handle(
                    $folio,
                    'restaurant',
                    "Restaurant order #{$order->id} — {$order->outlet->name}",
                    $order->total_cents,
                    $staff,
                );
                $order->update(['folio_id' => $folio->id]);
            } else {
                $this->restaurantLedger->postDirectSale($order, $staff);
            }

            $order->update(['status' => OrderStatus::Closed]);
            $order->table?->update(['status' => TableStatus::Free]);

            return $order;
        });
    }

    private function deductIngredients(RestaurantOrder $order, User $staff): void
    {
        foreach ($order->items as $orderItem) {
            $ingredients = MenuItemIngredient::where('menu_item_id', $orderItem->menu_item_id)->get();

            foreach ($ingredients as $ingredient) {
                $totalQuantity = (int) ceil($ingredient->quantity * $orderItem->quantity);

                if ($totalQuantity > 0) {
                    $this->issueStock->handle($ingredient->inventoryItem, $totalQuantity, $staff, $order);
                }
            }
        }
    }

    private function resolveOpenFolioForGuest(RestaurantOrder $order): ?Folio
    {
        if (! $order->guest_id) {
            return null;
        }

        return Folio::where('guest_id', $order->guest_id)->where('status', 'open')->first();
    }
}
