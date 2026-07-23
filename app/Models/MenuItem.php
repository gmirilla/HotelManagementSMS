<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['menu_category_id', 'name', 'price_cents', 'tax_class', 'is_available'])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<MenuCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    /**
     * @return HasMany<MenuItemIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(MenuItemIngredient::class);
    }
}
