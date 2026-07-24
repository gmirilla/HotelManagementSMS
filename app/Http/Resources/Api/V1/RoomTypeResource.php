<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Override;

#[OA\Schema(schema: 'RoomType', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
    new OA\Property(property: 'name', type: 'string', example: 'Deluxe'),
    new OA\Property(property: 'slug', type: 'string', example: 'deluxe'),
    new OA\Property(property: 'base_capacity_adults', type: 'integer', example: 2),
    new OA\Property(property: 'base_capacity_children', type: 'integer', example: 1),
    new OA\Property(property: 'base_rate_cents', type: 'integer', example: 15000),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'is_active', type: 'boolean', example: true),
], type: 'object')]
class RoomTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'base_capacity_adults' => $this->base_capacity_adults,
            'base_capacity_children' => $this->base_capacity_children,
            'base_rate_cents' => $this->base_rate_cents,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
