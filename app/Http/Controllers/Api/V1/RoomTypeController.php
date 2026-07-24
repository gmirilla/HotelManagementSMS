<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reservation\Support\AvailabilityChecker;
use App\Http\Resources\Api\V1\RoomTypeResource;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

class RoomTypeController extends Controller
{
    #[OA\Get(path: '/room-types', summary: 'List room types for a branch', security: [['sanctum' => []]], tags: ['Rooms'], parameters: [new OA\Parameter(name: 'branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Room types', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RoomType')),
        ])),
        new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RoomType::class);

        $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']]);

        abort_unless($request->user()->canAccessBranch($request->integer('branch_id')), 403);

        $roomTypes = RoomType::where('branch_id', $request->integer('branch_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => RoomTypeResource::collection($roomTypes)]);
    }

    #[OA\Get(path: '/room-types/{roomType}/availability', summary: 'Check room availability for a date range', security: [['sanctum' => []]], tags: ['Rooms'], parameters: [
        new OA\Parameter(name: 'roomType', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'arrival_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'departure_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
    ], responses: [
        new OA\Response(response: 200, description: 'Availability', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'room_type_id', type: 'integer'),
                new OA\Property(property: 'available_rooms', type: 'integer'),
            ], type: 'object'),
        ])),
    ])]
    public function availability(Request $request, RoomType $roomType, AvailabilityChecker $availabilityChecker): JsonResponse
    {
        $this->authorize('view', $roomType);

        $validated = $request->validate([
            'arrival_date' => ['required', 'date'],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
        ]);

        $arrival = Carbon::parse($validated['arrival_date']);
        $departure = Carbon::parse($validated['departure_date']);

        return response()->json(['data' => [
            'room_type_id' => $roomType->id,
            'available_rooms' => $availabilityChecker->availableRoomCount($roomType, $arrival, $departure),
        ]]);
    }
}
