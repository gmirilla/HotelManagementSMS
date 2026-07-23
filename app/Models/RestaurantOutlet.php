<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Restaurant\Enums\OutletType;
use Database\Factories\RestaurantOutletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['branch_id', 'name', 'outlet_type'])]
class RestaurantOutlet extends Model
{
    /** @use HasFactory<RestaurantOutletFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'outlet_type' => OutletType::class,
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<RestaurantTable, $this>
     */
    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'outlet_id');
    }

    /**
     * @return HasMany<MenuCategory, $this>
     */
    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class, 'outlet_id');
    }

    /**
     * @return HasMany<RestaurantOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class, 'outlet_id');
    }
}
