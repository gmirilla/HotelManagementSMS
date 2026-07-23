<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folio>
 */
class FolioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'reservation_id' => Reservation::factory(),
            'guest_id' => Guest::factory(),
            'status' => FolioStatus::Open,
            'balance_cents' => 0,
            'closed_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FolioStatus::Closed,
            'balance_cents' => 0,
            'closed_at' => now(),
        ]);
    }
}
