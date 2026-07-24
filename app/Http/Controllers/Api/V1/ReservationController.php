<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reservation\Actions\CancelReservationAction;
use App\Domain\Reservation\Actions\CreateReservationAction;
use App\Http\Requests\Api\V1\CancelReservationRequest;
use App\Http\Requests\Api\V1\StoreReservationRequest;
use App\Http\Resources\Api\V1\ReservationResource;
use App\Models\Reservation;
use App\Models\RoomType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

class ReservationController extends Controller
{
    #[OA\Get(path: '/reservations', summary: 'List bookings', security: [['sanctum' => []]], tags: ['Bookings'], parameters: [
        new OA\Parameter(name: 'branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'guest_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
    ], responses: [
        new OA\Response(response: 200, description: 'Paginated bookings', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Reservation')),
            new OA\Property(property: 'meta', type: 'object'),
        ])),
    ])]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);

        $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']]);
        abort_unless($request->user()->canAccessBranch($request->integer('branch_id')), 403);

        /** @var LengthAwarePaginator<int, Reservation> $reservations */
        $reservations = Reservation::where('branch_id', $request->integer('branch_id'))
            ->when($request->filled('guest_id'), fn ($query) => $query->where('guest_id', $request->integer('guest_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->with('rooms')
            ->orderByDesc('arrival_date')
            ->paginate(20);

        return response()->json([
            'data' => ReservationResource::collection($reservations->items()),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'total' => $reservations->total(),
            ],
        ]);
    }

    #[OA\Post(path: '/reservations', summary: 'Create a booking', security: [['sanctum' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['branch_id', 'guest_id', 'room_type_id', 'arrival_date', 'departure_date', 'adults'],
        properties: [
            new OA\Property(property: 'branch_id', type: 'integer'),
            new OA\Property(property: 'guest_id', type: 'integer'),
            new OA\Property(property: 'room_type_id', type: 'integer'),
            new OA\Property(property: 'arrival_date', type: 'string', format: 'date'),
            new OA\Property(property: 'departure_date', type: 'string', format: 'date'),
            new OA\Property(property: 'adults', type: 'integer', example: 2),
            new OA\Property(property: 'children', type: 'integer', example: 0),
            new OA\Property(property: 'source', type: 'string', example: 'online'),
            new OA\Property(property: 'corporate_account_id', type: 'integer', nullable: true),
            new OA\Property(property: 'special_requests', type: 'string', nullable: true),
        ],
    )), tags: ['Bookings'], responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Reservation'),
        ])),
        new OA\Response(response: 422, description: 'No availability / validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function store(StoreReservationRequest $request, CreateReservationAction $createReservation): JsonResponse
    {
        $validated = $request->validated();

        abort_unless($request->user()->canAccessBranch($validated['branch_id']), 403);

        $roomType = RoomType::findOrFail($validated['room_type_id']);

        $reservation = $createReservation->handle(
            branchId: $validated['branch_id'],
            guestId: $validated['guest_id'],
            roomType: $roomType,
            arrival: Carbon::parse($validated['arrival_date']),
            departure: Carbon::parse($validated['departure_date']),
            adults: $validated['adults'],
            children: $validated['children'] ?? 0,
            source: $validated['source'] ?? 'online',
            corporateAccountId: $validated['corporate_account_id'] ?? null,
            specialRequests: $validated['special_requests'] ?? null,
        );

        return response()->json(['data' => new ReservationResource($reservation->load('rooms'))], 201);
    }

    #[OA\Get(path: '/reservations/{reservation}', summary: 'Show a booking', security: [['sanctum' => []]], tags: ['Bookings'], parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Booking', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Reservation'),
        ])),
        new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function show(Reservation $reservation): JsonResponse
    {
        $this->authorize('view', $reservation);

        return response()->json(['data' => new ReservationResource($reservation->load('rooms'))]);
    }

    #[OA\Post(path: '/reservations/{reservation}/cancel', summary: 'Cancel a booking', security: [['sanctum' => []]], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'reason', type: 'string', nullable: true),
    ])), tags: ['Bookings'], parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Cancelled', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Reservation'),
        ])),
        new OA\Response(response: 422, description: 'Booking cannot be cancelled', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function cancel(CancelReservationRequest $request, Reservation $reservation, CancelReservationAction $cancelReservation): JsonResponse
    {
        $cancelReservation->handle($reservation, $request->user(), $request->validated('reason'));

        return response()->json(['data' => new ReservationResource($reservation->fresh('rooms'))]);
    }
}
