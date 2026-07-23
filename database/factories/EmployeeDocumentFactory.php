<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'document_type' => fake()->randomElement(['id_card', 'passport', 'work_permit', 'contract']),
            'reference_number' => fake()->bothify('??######'),
            'expires_on' => fake()->dateTimeBetween('+6 months', '+3 years'),
        ];
    }
}
