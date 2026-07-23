<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company(),
            'contact_email' => fake()->companyEmail(),
            'payment_terms' => fake()->randomElement(['Net 15', 'Net 30', 'Net 45', 'Due on receipt']),
        ];
    }
}
