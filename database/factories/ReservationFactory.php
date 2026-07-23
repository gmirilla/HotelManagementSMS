<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Reservation\Enums\ReservationSource;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $arrival = fake()->dateTimeBetween('-5 days', '+30 days');
        $departure = (clone $arrival)->modify('+' . fake()->numberBetween(1, 7) . ' days');

        return [
            'branch_id' => Branch::factory(),
            'guest_id' => Guest::factory(),
            'corporate_account_id' => null,
            'confirmation_code' => Str::upper(Str::random(8)),
            'source' => fake()->randomElement(ReservationSource::cases()),
            'status' => ReservationStatus::Confirmed,
            'arrival_date' => $arrival,
            'departure_date' => $departure,
            'adults' => fake()->numberBetween(1, 3),
            'children' => fake()->numberBetween(0, 2),
            'special_requests' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReservationStatus::Pending]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReservationStatus::CheckedIn]);
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReservationStatus::CheckedOut]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_fee_cents' => fake()->numberBetween(0, 5000),
        ]);
    }
}
