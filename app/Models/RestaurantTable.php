<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Restaurant\Enums\TableStatus;
use Database\Factories\RestaurantTableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['outlet_id', 'label', 'seats', 'status'])]
class RestaurantTable extends Model
{
    /** @use HasFactory<RestaurantTableFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'status' => TableStatus::class,
        ];
    }

    /**
     * @return BelongsTo<RestaurantOutlet, $this>
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(RestaurantOutlet::class, 'outlet_id');
    }
}
