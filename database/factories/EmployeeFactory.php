<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\EmployeeStatus;
use App\Domain\HR\Enums\EmploymentType;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'employee_number' => 'EMP-' . fake()->unique()->numberBetween(10000, 99999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'department' => fake()->randomElement(['Front Office', 'Housekeeping', 'F&B', 'Maintenance', 'Accounting', 'HR']),
            'job_title' => fake()->jobTitle(),
            'employment_type' => EmploymentType::FullTime,
            'status' => EmployeeStatus::Active,
            'hire_date' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'base_salary_cents' => fake()->numberBetween(250000, 900000),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
