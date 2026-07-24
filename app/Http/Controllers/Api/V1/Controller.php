<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', description: 'Versioned REST API for authentication, bookings, guests, rooms, invoices, payments, and reports (FR-API-001). All responses use consistent API Resource transformers; all errors share a single {"error": {code, message, errors?}} envelope (FR-API-003).', title: 'Aurora Hotels — Hotel Management System API')]
#[OA\Server(url: '/api/v1', description: 'Default API server')]
#[OA\SecurityScheme(securityScheme: 'sanctum', type: 'http', description: 'Sanctum personal access token. Send as `Authorization: Bearer {token}`. Tokens are scoped by ability (FR-API-004) — see the abilities returned at login.', bearerFormat: 'Opaque token issued by POST /auth/login', scheme: 'bearer')]
#[OA\Schema(schema: 'ErrorEnvelope', properties: [
    new OA\Property(property: 'error', properties: [
        new OA\Property(property: 'code', type: 'string', example: 'validation_error'),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'errors', type: 'object', nullable: true, additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))),
    ], type: 'object'),
], type: 'object')]
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
