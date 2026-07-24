<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\FolioResource;
use App\Models\Folio;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class FolioController extends Controller
{
    #[OA\Get(path: '/folios/{folio}', summary: 'Show an invoice (folio) with its charges and payments', security: [['sanctum' => []]], tags: ['Invoices'], parameters: [new OA\Parameter(name: 'folio', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 200, description: 'Folio', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Folio'),
        ])),
        new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function show(Folio $folio): JsonResponse
    {
        $this->authorize('view', $folio);

        return response()->json(['data' => new FolioResource($folio->load('charges', 'payments'))]);
    }
}
