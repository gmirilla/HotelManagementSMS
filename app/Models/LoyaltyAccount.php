<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LoyaltyAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['guest_id', 'enrolled_at'])]
class LoyaltyAccount extends Model
{
    /** @use HasFactory<LoyaltyAccountFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return HasMany<LoyaltyTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}
