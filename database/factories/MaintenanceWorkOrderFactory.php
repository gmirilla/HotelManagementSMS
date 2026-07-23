<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Maintenance\Enums\WorkOrderPriority;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Models\Branch;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceWorkOrder>
 */
class MaintenanceWorkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'reported_by_user_id' => User::factory(),
            'priority' => WorkOrderPriority::Medium,
            'status' => WorkOrderStatus::Open,
            'description' => fake()->sentence(8),
            'parts_cost_cents' => 0,
            'labor_cost_cents' => 0,
            'is_preventive' => false,
        ];
    }

    public function preventive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preventive' => true,
            'recurrence_days' => 90,
        ]);
    }
}
