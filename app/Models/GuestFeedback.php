<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\CRM\Enums\FeedbackStatus;
use App\Domain\CRM\Enums\FeedbackType;
use Database\Factories\GuestFeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'guest_id', 'type', 'subject', 'description', 'status', 'assigned_to_user_id', 'resolution_notes', 'resolved_at'])]
class GuestFeedback extends Model
{
    /** @use HasFactory<GuestFeedbackFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'type' => FeedbackType::class,
            'status' => FeedbackStatus::class,
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
