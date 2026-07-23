<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Accounting\Enums\TaxAppliesTo;
use Database\Factories\TaxRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'name', 'rate_percent', 'applies_to', 'is_active'])]
class TaxRule extends Model
{
    /** @use HasFactory<TaxRuleFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'applies_to' => TaxAppliesTo::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
