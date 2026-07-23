<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Guest\Enums\DocumentType;
use App\Models\Guest;
use App\Models\GuestDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestDocument>
 */
class GuestDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'document_type' => DocumentType::Passport,
            'document_number' => strtoupper(fake()->bothify('??######')),
            'issuing_country' => fake()->countryCode(),
            'expires_on' => fake()->dateTimeBetween('+1 year', '+10 years'),
        ];
    }
}
