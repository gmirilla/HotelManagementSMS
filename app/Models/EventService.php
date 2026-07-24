<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Event\Enums\EventServiceCategory;
use Database\Factories\EventServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'name', 'category', 'unit_price_cents', 'unit', 'is_active'])]
class EventService extends Model
{
    /** @use HasFactory<EventServiceFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'category' => EventServiceCategory::class,
            'unit_price_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
