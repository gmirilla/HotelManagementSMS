<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Override;

#[OA\Schema(schema: 'Folio', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
    new OA\Property(property: 'reservation_id', type: 'integer', nullable: true),
    new OA\Property(property: 'guest_id', type: 'integer', example: 1),
    new OA\Property(property: 'status', type: 'string', example: 'open'),
    new OA\Property(property: 'balance_cents', type: 'integer', example: 15000),
    new OA\Property(property: 'charges', type: 'array', items: new OA\Items(
        properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'charge_type', type: 'string'),
            new OA\Property(property: 'description', type: 'string'),
            new OA\Property(property: 'amount_cents', type: 'integer'),
            new OA\Property(property: 'charge_date', type: 'string', format: 'date'),
        ],
        type: 'object',
    )),
    new OA\Property(property: 'payments', type: 'array', items: new OA\Items(
        properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'method', type: 'string'),
            new OA\Property(property: 'amount_cents', type: 'integer'),
            new OA\Property(property: 'status', type: 'string'),
        ],
        type: 'object',
    )),
], type: 'object')]
class FolioResource extends JsonResource
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
            'reservation_id' => $this->reservation_id,
            'guest_id' => $this->guest_id,
            'status' => $this->status->value,
            'balance_cents' => $this->balance_cents,
            'charges' => $this->whenLoaded('charges', fn () => $this->charges->map(fn ($charge) => [
                'id' => $charge->id,
                'charge_type' => $charge->charge_type->value,
                'description' => $charge->description,
                'amount_cents' => $charge->amount_cents,
                'charge_date' => $charge->charge_date->toDateString(),
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'method' => $payment->method->value,
                'amount_cents' => $payment->amount_cents,
                'status' => $payment->status->value,
            ])),
        ];
    }
}
