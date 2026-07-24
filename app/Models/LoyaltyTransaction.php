<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\CRM\Enums\LoyaltyTransactionType;
use Database\Factories\LoyaltyTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

#[Fillable(['loyalty_account_id', 'transaction_type', 'points', 'description', 'reference_type', 'reference_id', 'transaction_date', 'created_by_user_id'])]
class LoyaltyTransaction extends Model
{
    /** @use HasFactory<LoyaltyTransactionFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'transaction_type' => LoyaltyTransactionType::class,
            'points' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<LoyaltyAccount, $this>
     */
    public function loyaltyAccount(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
