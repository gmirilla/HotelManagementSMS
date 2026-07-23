<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    public function definition(): array
    {
        $basic = fake()->numberBetween(250000, 900000);

        return [
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'basic_cents' => $basic,
            'allowances_cents' => 0,
            'deductions_cents' => 0,
            'gross_cents' => $basic,
            'net_cents' => $basic,
            'days_present' => 22,
            'days_absent' => 0,
            'days_on_leave' => 0,
        ];
    }
}
