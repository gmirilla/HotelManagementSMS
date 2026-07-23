<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Guest\Enums\GuestContactRelationType;
use Database\Factories\GuestContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['guest_id', 'relation_type', 'name', 'phone', 'relationship'])]
class GuestContact extends Model
{
    /** @use HasFactory<GuestContactFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'relation_type' => GuestContactRelationType::class,
        ];
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
