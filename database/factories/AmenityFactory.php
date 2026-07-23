<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Free Wi-Fi', 'Air Conditioning', 'Mini Bar', 'Sea View', 'Balcony',
                'Room Service', 'Flat-Screen TV', 'Coffee Maker', 'Safe', 'Bathtub',
            ]),
            'icon' => 'heroicon-o-check-circle',
        ];
    }
}
