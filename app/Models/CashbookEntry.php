<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Accounting\Enums\CashbookEntryType;
use Database\Factories\CashbookEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'cashier_user_id', 'entry_type', 'amount_cents', 'reason', 'shift_date', 'reconciled'])]
class CashbookEntry extends Model
{
    /** @use HasFactory<CashbookEntryFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'entry_type' => CashbookEntryType::class,
            'amount_cents' => 'integer',
            'shift_date' => 'date',
            'reconciled' => 'boolean',
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
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function signedAmountCents(): int
    {
        return $this->entry_type === CashbookEntryType::CashIn ? $this->amount_cents : -$this->amount_cents;
    }
}
