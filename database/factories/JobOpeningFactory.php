<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\JobOpeningStatus;
use App\Models\Branch;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOpening>
 */
class JobOpeningFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Front Office', 'Housekeeping', 'F&B', 'Maintenance']),
            'description' => fake()->paragraph(),
            'status' => JobOpeningStatus::Open,
        ];
    }
}
