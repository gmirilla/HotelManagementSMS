<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guest;
use App\Models\LoyaltyAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyAccount>
 */
class LoyaltyAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'enrolled_at' => now()->toDateString(),
        ];
    }
}
