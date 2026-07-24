<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\CRM\Enums\FeedbackStatus;
use App\Domain\CRM\Enums\FeedbackType;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\GuestFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestFeedback>
 */
class GuestFeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'guest_id' => Guest::factory(),
            'type' => FeedbackType::Complaint,
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => FeedbackStatus::Open,
        ];
    }
}
