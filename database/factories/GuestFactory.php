<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Guest\Enums\GuestFlag;
use App\Domain\Guest\Enums\GuestType;
use App\Models\Guest;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'nationality' => fake()->countryCode(),
            'guest_type' => GuestType::Individual,
            'flag' => GuestFlag::None,
            'preferences' => [
                'room_floor' => fake()->randomElement(['low', 'high', 'no_preference']),
                'bed_type' => fake()->randomElement(['king', 'twin']),
            ],
        ];
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => ['flag' => GuestFlag::Vip]);
    }

    public function blacklisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'flag' => GuestFlag::Blacklisted,
            'blacklist_reason' => fake()->sentence(),
        ]);
    }
}
