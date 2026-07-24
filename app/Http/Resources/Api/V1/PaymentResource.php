<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Override;

#[OA\Schema(schema: 'Payment', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'folio_id', type: 'integer', example: 1),
    new OA\Property(property: 'method', type: 'string', example: 'cash'),
    new OA\Property(property: 'amount_cents', type: 'integer', example: 5000),
    new OA\Property(property: 'currency', type: 'string', example: 'USD'),
    new OA\Property(property: 'status', type: 'string', example: 'completed'),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
], type: 'object')]
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio_id' => $this->folio_id,
            'method' => $this->method->value,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
