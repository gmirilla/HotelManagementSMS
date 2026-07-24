<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reporting\Support\OccupancyReportCalculator;
use App\Domain\Reporting\Support\RevenueReportCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    #[OA\Get(path: '/reports/occupancy', summary: 'Occupancy rate for a branch on a given date', security: [['sanctum' => []]], tags: ['Reports'], parameters: [
        new OA\Parameter(name: 'branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(description: 'Defaults to today', type: 'string', format: 'date')),
    ], responses: [new OA\Response(response: 200, description: 'Occupancy report', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'data', properties: [
            new OA\Property(property: 'date', type: 'string', format: 'date'),
            new OA\Property(property: 'total_rooms', type: 'integer'),
            new OA\Property(property: 'occupied_rooms', type: 'integer'),
            new OA\Property(property: 'occupancy_rate', type: 'number', format: 'float', example: 0.72),
        ], type: 'object'),
    ]))])]
    public function occupancy(Request $request, OccupancyReportCalculator $calculator): JsonResponse
    {
        $this->authorizeReportAccess($request);

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'date' => ['sometimes', 'date'],
        ]);

        abort_unless($request->user()->canAccessBranch($validated['branch_id']), 403);

        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : now();

        return response()->json(['data' => $calculator->forDate($validated['branch_id'], $date)]);
    }

    #[OA\Get(path: '/reports/revenue', summary: 'Revenue for a branch over a date range, broken down by charge type', security: [['sanctum' => []]], tags: ['Reports'], parameters: [
        new OA\Parameter(name: 'branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'start', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'end', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
    ], responses: [new OA\Response(response: 200, description: 'Revenue report', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'data', properties: [
            new OA\Property(property: 'start', type: 'string', format: 'date'),
            new OA\Property(property: 'end', type: 'string', format: 'date'),
            new OA\Property(property: 'total_cents', type: 'integer'),
            new OA\Property(property: 'by_charge_type', type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'charge_type', type: 'string'),
                    new OA\Property(property: 'amount_cents', type: 'integer'),
                ],
                type: 'object',
            )),
        ], type: 'object'),
    ]))])]
    public function revenue(Request $request, RevenueReportCalculator $calculator): JsonResponse
    {
        $this->authorizeReportAccess($request);

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        abort_unless($request->user()->canAccessBranch($validated['branch_id']), 403);

        $report = $calculator->forPeriod($validated['branch_id'], Carbon::parse($validated['start']), Carbon::parse($validated['end']));

        return response()->json(['data' => $report]);
    }

    private function authorizeReportAccess(Request $request): void
    {
        abort_unless($request->user()->hasPermissionTo('reports.view'), 403);
    }
}
