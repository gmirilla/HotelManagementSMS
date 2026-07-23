<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Accounting\Enums\ApStatus;
use Database\Factories\ApEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'supplier_id', 'purchase_order_id', 'amount_cents', 'paid_cents', 'due_date', 'status'])]
class ApEntry extends Model
{
    /** @use HasFactory<ApEntryFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_cents' => 'integer',
            'due_date' => 'date',
            'status' => ApStatus::class,
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
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function outstandingCents(): int
    {
        return max(0, $this->amount_cents - $this->paid_cents);
    }

    public function isOverdue(): bool
    {
        return $this->status !== ApStatus::Paid && $this->due_date->isPast();
    }
}
