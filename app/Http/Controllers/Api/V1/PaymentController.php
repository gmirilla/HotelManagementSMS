<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payment\Actions\RecordFolioPaymentAction;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Folio;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    #[OA\Post(path: '/folios/{folio}/payments', summary: 'Record a payment against an invoice (cash, POS, or bank transfer only — gateway methods are webhook-driven)', security: [['sanctum' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['method', 'amount'],
        properties: [
            new OA\Property(property: 'method', type: 'string', enum: ['cash', 'pos_terminal', 'bank_transfer']),
            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150.00),
        ],
    )), tags: ['Payments'], parameters: [new OA\Parameter(name: 'folio', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
        new OA\Response(response: 201, description: 'Payment recorded', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Payment'),
        ])),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function store(StorePaymentRequest $request, Folio $folio, RecordFolioPaymentAction $recordPayment): JsonResponse
    {
        $payment = $recordPayment->handle(
            $folio,
            $request->string('method')->toString(),
            (int) round($request->float('amount') * 100),
            $request->user(),
        );

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }
}
