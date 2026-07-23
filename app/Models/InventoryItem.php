<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable([
    'warehouse_id', 'sku', 'name', 'unit_of_measure', 'barcode', 'reorder_point',
    'quantity_on_hand', 'average_cost_cents', 'expires_on', 'is_perishable',
])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory, SoftDeletes;

    #[Override]
    protected function casts(): array
    {
        return [
            'reorder_point' => 'integer',
            'quantity_on_hand' => 'integer',
            'average_cost_cents' => 'integer',
            'expires_on' => 'date',
            'is_perishable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isBelowReorderPoint(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_point;
    }

    public function isNearExpiry(int $withinDays = 7): bool
    {
        return $this->is_perishable
            && $this->expires_on !== null
            && $this->expires_on->lte(now()->addDays($withinDays));
    }
}
