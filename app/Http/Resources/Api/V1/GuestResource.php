<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Override;

#[OA\Schema(schema: 'Guest', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
    new OA\Property(property: 'phone', type: 'string', nullable: true),
    new OA\Property(property: 'nationality', type: 'string', nullable: true),
    new OA\Property(property: 'guest_type', type: 'string', example: 'individual'),
    new OA\Property(property: 'flag', type: 'string', example: 'none'),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
], type: 'object')]
class GuestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'nationality' => $this->nationality,
            'guest_type' => $this->guest_type->value,
            'flag' => $this->flag->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
