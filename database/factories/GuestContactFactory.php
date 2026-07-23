<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Guest\Enums\GuestContactRelationType;
use App\Models\Guest;
use App\Models\GuestContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestContact>
 */
class GuestContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'relation_type' => GuestContactRelationType::Emergency,
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
        ];
    }
}
