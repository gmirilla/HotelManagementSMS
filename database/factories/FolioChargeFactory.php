<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Models\Folio;
use App\Models\FolioCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FolioCharge>
 */
class FolioChargeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'folio_id' => Folio::factory(),
            'charge_type' => ChargeType::Room,
            'description' => 'Room charge',
            'amount_cents' => fake()->numberBetween(8000, 45000),
            'charge_date' => now()->toDateString(),
            'posted_by_user_id' => null,
        ];
    }
}
