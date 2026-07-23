<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

#[Fillable(['branch_id', 'entry_date', 'reference_type', 'reference_id', 'memo', 'created_by_user_id'])]
class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function totalDebitCents(): int
    {
        return (int) $this->lines()->where('side', 'debit')->sum('amount_cents');
    }

    public function totalCreditCents(): int
    {
        return (int) $this->lines()->where('side', 'credit')->sum('amount_cents');
    }

    public function isBalanced(): bool
    {
        return $this->totalDebitCents() === $this->totalCreditCents();
    }
}
