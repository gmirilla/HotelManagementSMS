<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\DisciplinarySeverity;
use App\Models\DisciplinaryRecord;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisciplinaryRecord>
 */
class DisciplinaryRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'reported_by_user_id' => User::factory(),
            'incident_date' => now()->toDateString(),
            'severity' => DisciplinarySeverity::VerbalWarning,
            'description' => fake()->sentence(10),
        ];
    }
}
