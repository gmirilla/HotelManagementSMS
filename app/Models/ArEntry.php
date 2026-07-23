<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Accounting\Enums\ArStatus;
use Database\Factories\ArEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'corporate_account_id', 'folio_id', 'amount_cents', 'paid_cents', 'due_date', 'status'])]
class ArEntry extends Model
{
    /** @use HasFactory<ArEntryFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_cents' => 'integer',
            'due_date' => 'date',
            'status' => ArStatus::class,
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
     * @return BelongsTo<CorporateAccount, $this>
     */
    public function corporateAccount(): BelongsTo
    {
        return $this->belongsTo(CorporateAccount::class);
    }

    /**
     * @return BelongsTo<Folio, $this>
     */
    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function outstandingCents(): int
    {
        return max(0, $this->amount_cents - $this->paid_cents);
    }

    public function isOverdue(): bool
    {
        return $this->status !== ArStatus::Paid && $this->status !== ArStatus::WrittenOff && $this->due_date->isPast();
    }
}
