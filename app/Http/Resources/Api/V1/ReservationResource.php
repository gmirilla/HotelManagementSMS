<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Override;

#[OA\Schema(schema: 'Reservation', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'confirmation_code', type: 'string', example: 'RES-A1B2C3'),
    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
    new OA\Property(property: 'guest_id', type: 'integer', example: 1),
    new OA\Property(property: 'status', type: 'string', example: 'confirmed'),
    new OA\Property(property: 'source', type: 'string', example: 'online'),
    new OA\Property(property: 'arrival_date', type: 'string', format: 'date'),
    new OA\Property(property: 'departure_date', type: 'string', format: 'date'),
    new OA\Property(property: 'nights', type: 'integer', example: 3),
    new OA\Property(property: 'adults', type: 'integer', example: 2),
    new OA\Property(property: 'children', type: 'integer', example: 0),
    new OA\Property(property: 'special_requests', type: 'string', nullable: true),
    new OA\Property(property: 'cancellation_fee_cents', type: 'integer', nullable: true),
    new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'rooms', type: 'array', items: new OA\Items(
        properties: [
            new OA\Property(property: 'room_type_id', type: 'integer'),
            new OA\Property(property: 'room_id', type: 'integer', nullable: true),
            new OA\Property(property: 'rate_cents', type: 'integer'),
        ],
        type: 'object',
    )),
], type: 'object')]
class ReservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'confirmation_code' => $this->confirmation_code,
            'branch_id' => $this->branch_id,
            'guest_id' => $this->guest_id,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'arrival_date' => $this->arrival_date->toDateString(),
            'departure_date' => $this->departure_date->toDateString(),
            'nights' => $this->nights(),
            'adults' => $this->adults,
            'children' => $this->children,
            'special_requests' => $this->special_requests,
            'cancellation_fee_cents' => $this->cancellation_fee_cents,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'rooms' => $this->whenLoaded('rooms', fn () => $this->rooms->map(fn ($room) => [
                'room_type_id' => $room->room_type_id,
                'room_id' => $room->room_id,
                'rate_cents' => $room->rate_cents,
            ])),
        ];
    }
}
