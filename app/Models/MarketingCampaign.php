<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\CRM\Enums\MarketingCampaignChannel;
use App\Domain\CRM\Enums\MarketingCampaignStatus;
use Database\Factories\MarketingCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['branch_id', 'name', 'channel', 'segment_criteria', 'message', 'status', 'scheduled_at', 'sent_at'])]
class MarketingCampaign extends Model
{
    /** @use HasFactory<MarketingCampaignFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'channel' => MarketingCampaignChannel::class,
            'segment_criteria' => 'array',
            'status' => MarketingCampaignStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
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
