<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Room\Enums\RoomRateType;
use Database\Factories\RoomRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['room_type_id', 'rate_type', 'starts_on', 'ends_on', 'days_of_week', 'rate_cents', 'priority'])]
class RoomRate extends Model
{
    /** @use HasFactory<RoomRateFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'rate_type' => RoomRateType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'days_of_week' => 'array',
            'rate_cents' => 'integer',
            'priority' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
