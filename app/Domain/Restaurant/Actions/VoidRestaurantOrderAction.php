<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Models\RestaurantOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Voiding is for an order that should never have been rung up in the first
 * place (a mis-tap, a duplicate, a guest who walked out before anything was
 * prepared) — not a correction for one that's already been settled. Once an
 * order is Closed it's already recognized revenue and, for room-service,
 * already posted to a folio (FR-ACC-002), so it can't be voided any more
 * than a posted journal entry can be edited in place — a wrong Closed order
 * needs a real reversal, not a void. Ingredients are only deducted at close
 * time (FR-POS-006), so a voided order never needs to reverse inventory
 * either — there's nothing to undo yet.
 */
class VoidRestaurantOrderAction
{
    public function handle(RestaurantOrder $order, string $reason): RestaurantOrder
    {
        if (! in_array($order->status, [OrderStatus::Open, OrderStatus::SentToKitchen, OrderStatus::Served], true)) {
            throw ValidationException::withMessages(['status' => __('This order cannot be voided from its current status.')]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('A reason is required to void an order.')]);
        }

        return DB::transaction(function () use ($order, $reason) {
            $order->update(['status' => OrderStatus::Voided, 'void_reason' => $reason]);
            $order->table?->update(['status' => TableStatus::Free]);

            return $order;
        });
    }
}
