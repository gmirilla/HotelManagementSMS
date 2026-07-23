<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        $city = fake()->city();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $city . ' ' . fake()->randomElement(['Grand Hotel', 'Plaza', 'Resort & Spa', 'Suites']),
            'code' => Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $city), 0, 3)) . '-' . fake()->unique()->numberBetween(10, 99),
            'currency' => 'USD',
            'timezone' => 'UTC',
            'address_line1' => fake()->streetAddress(),
            'city' => $city,
            'country' => fake()->country(),
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'cancellation_policy' => [
                'free_cancellation_hours' => 48,
                'fee_percent' => 50,
            ],
            'is_active' => true,
        ];
    }
}
