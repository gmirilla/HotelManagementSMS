<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'folio_id' => Folio::factory(),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'amount_cents' => fake()->numberBetween(8000, 45000),
            'currency' => 'USD',
            'status' => PaymentStatus::Completed,
            'gateway_reference' => fake()->uuid(),
            'received_by_user_id' => null,
        ];
    }
}
