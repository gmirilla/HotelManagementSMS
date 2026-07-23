<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\FrontDesk\Enums\ChargeType;
use Database\Factories\FolioChargeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['folio_id', 'charge_type', 'description', 'amount_cents', 'charge_date', 'posted_by_user_id'])]
class FolioCharge extends Model
{
    /** @use HasFactory<FolioChargeFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    #[Override]
    protected function casts(): array
    {
        return [
            'charge_type' => ChargeType::class,
            'amount_cents' => 'integer',
            'charge_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Folio, $this>
     */
    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }
}
