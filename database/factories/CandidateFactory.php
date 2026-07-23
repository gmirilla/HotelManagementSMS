<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\CandidateStage;
use App\Models\Candidate;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_opening_id' => JobOpening::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'stage' => CandidateStage::Applied,
        ];
    }
}
