<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GuestNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['guest_id', 'created_by_user_id', 'note', 'is_alert'])]
class GuestNote extends Model
{
    /** @use HasFactory<GuestNoteFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'is_alert' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
