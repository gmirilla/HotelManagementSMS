<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guest;
use App\Models\GuestNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestNote>
 */
class GuestNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'created_by_user_id' => null,
            'note' => fake()->sentence(10),
            'is_alert' => false,
        ];
    }

    public function alert(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_alert' => true,
            'note' => fake()->randomElement([
                'Guest has a severe nut allergy.',
                'Guest requires wheelchair-accessible room.',
                'Guest requested no housekeeping before 10am.',
            ]),
        ]);
    }
}
