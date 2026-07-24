<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\CRM\Enums\MarketingCampaignChannel;
use App\Domain\CRM\Enums\MarketingCampaignStatus;
use App\Models\Branch;
use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingCampaign>
 */
class MarketingCampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->catchPhrase(),
            'channel' => MarketingCampaignChannel::Email,
            'message' => fake()->paragraph(),
            'status' => MarketingCampaignStatus::Draft,
        ];
    }
}
