<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\OrderType;
use App\Models\Branch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOutlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantOrder>
 */
class RestaurantOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'outlet_id' => RestaurantOutlet::factory(),
            'order_type' => OrderType::DineIn,
            'status' => OrderStatus::Open,
            'opened_by_user_id' => User::factory(),
        ];
    }
}
