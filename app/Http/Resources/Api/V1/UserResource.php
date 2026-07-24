<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Override;

#[OA\Schema(schema: 'User', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'name', type: 'string', example: 'Aurora Owner'),
    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'owner@aurorahotels.test'),
    new OA\Property(property: 'current_branch_id', type: 'integer', example: 1, nullable: true),
    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['Branch Manager']),
], type: 'object')]
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'current_branch_id' => $this->current_branch_id,
            'roles' => $this->getRoleNames(),
        ];
    }
}
