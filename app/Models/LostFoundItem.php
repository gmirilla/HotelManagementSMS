<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Housekeeping\Enums\LostFoundStatus;
use Database\Factories\LostFoundItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'room_id', 'description', 'found_by_user_id', 'found_on', 'status', 'returned_to_guest_id'])]
class LostFoundItem extends Model
{
    /** @use HasFactory<LostFoundItemFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'found_on' => 'date',
            'status' => LostFoundStatus::class,
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
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function foundBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'found_by_user_id');
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function returnedToGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'returned_to_guest_id');
    }
}
