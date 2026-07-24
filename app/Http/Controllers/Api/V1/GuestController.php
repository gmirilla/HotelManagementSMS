<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Guest\Enums\GuestFlag;
use App\Domain\Guest\Enums\GuestType;
use App\Http\Requests\Api\V1\StoreGuestRequest;
use App\Http\Requests\Api\V1\UpdateGuestRequest;
use App\Http\Resources\Api\V1\GuestResource;
use App\Models\Guest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GuestController extends Controller
{
    #[OA\Get(path: '/guests', summary: 'List/search guests', security: [['sanctum' => []]], tags: ['Guests'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
    ], responses: [
        new OA\Response(response: 200, description: 'Paginated guests', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Guest')),
            new OA\Property(property: 'meta', type: 'object'),
        ])),
    ])]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Guest::class);

        $search = $request->string('search')->toString();

        /** @var LengthAwarePaginator<int, Guest> $guests */
        $guests = Guest::where('tenant_id', $request->user()->tenant_id)
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('last_name')
            ->paginate(20);

        return response()->json([
            'data' => GuestResource::collection($guests->items()),
            'meta' => [
                'current_page' => $guests->currentPage(),
                'last_page' => $guests->lastPage(),
                'total' => $guests->total(),
            ],
        ]);
    }

    #[OA\Post(path: '/guests', summary: 'Create a guest profile', security: [['sanctum' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['first_name', 'last_name'],
        properties: [
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
            new OA\Property(property: 'phone', type: 'string', nullable: true),
            new OA\Property(property: 'nationality', type: 'string', nullable: true),
        ],
    )), tags: ['Guests'], responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Guest'),
        ])),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function store(StoreGuestRequest $request): JsonResponse
    {
        // guest_type/flag have database-level defaults, but Eloquent doesn't
        // re-fetch column defaults after insert — omitting them here would
        // leave the in-memory model's attributes null until a fresh query.
        $guest = Guest::create([
            'guest_type' => GuestType::Individual,
            'flag' => GuestFlag::None,
            ...$request->validated(),
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return response()->json(['data' => new GuestResource($guest)], 201);
    }

    #[OA\Get(path: '/guests/{guest}', summary: 'Show a guest profile', security: [['sanctum' => []]], tags: ['Guests'], parameters: [new OA\Parameter(name: 'guest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Guest', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Guest'),
        ])),
        new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function show(Guest $guest): JsonResponse
    {
        $this->authorize('view', $guest);

        return response()->json(['data' => new GuestResource($guest)]);
    }

    #[OA\Patch(path: '/guests/{guest}', summary: 'Update a guest profile', security: [['sanctum' => []]], requestBody: new OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
            new OA\Property(property: 'phone', type: 'string', nullable: true),
        ],
    )), tags: ['Guests'], parameters: [new OA\Parameter(name: 'guest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Guest'),
    ]))])]
    public function update(UpdateGuestRequest $request, Guest $guest): JsonResponse
    {
        $guest->update($request->validated());

        return response()->json(['data' => new GuestResource($guest)]);
    }
}
