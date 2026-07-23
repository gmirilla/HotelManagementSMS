<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\PerformanceRating;
use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceReview>
 */
class PerformanceReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'reviewer_user_id' => User::factory(),
            'review_period' => now()->format('Y') . ' H1',
            'review_date' => now()->toDateString(),
            'rating' => PerformanceRating::MeetsExpectations,
            'strengths' => fake()->sentence(8),
            'areas_for_improvement' => fake()->sentence(8),
            'comments' => fake()->sentence(10),
        ];
    }
}
