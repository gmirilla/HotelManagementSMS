<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Restaurant\Enums\KitchenStatus;
use Database\Factories\RestaurantOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['order_id', 'menu_item_id', 'quantity', 'unit_price_cents', 'modifiers', 'kitchen_status', 'split_group'])]
class RestaurantOrderItem extends Model
{
    /** @use HasFactory<RestaurantOrderItemFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'modifiers' => 'array',
            'kitchen_status' => KitchenStatus::class,
        ];
    }

    /**
     * @return BelongsTo<RestaurantOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class, 'order_id');
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function lineTotalCents(): int
    {
        return $this->quantity * $this->unit_price_cents;
    }
}
