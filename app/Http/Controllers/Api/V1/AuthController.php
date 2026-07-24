<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\Actions\AttemptLoginAction;
use App\Domain\Auth\Actions\VerifyMfaCodeAction;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const array PERMISSION_TO_ABILITY = [
        'reservations.view' => 'bookings:read',
        'reservations.create' => 'bookings:write',
        'reservations.update' => 'bookings:write',
        'reservations.manage' => 'bookings:write',
        'guests.view' => 'guests:read',
        'guests.create' => 'guests:write',
        'guests.manage' => 'guests:write',
        'folios.view' => 'invoices:read',
        'folios.manage' => 'invoices:read',
        'payments.process' => 'payments:write',
        'reports.view' => 'reports:read',
    ];

    #[OA\Post(path: '/auth/login', summary: 'Exchange credentials for a Sanctum API token', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['email', 'password', 'device_name'],
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
            new OA\Property(property: 'device_name', type: 'string', example: 'my-integration'),
            new OA\Property(property: 'mfa_code', description: 'Required if the account has MFA enabled', type: 'string', nullable: true),
        ],
    )), tags: ['Auth'], responses: [
        new OA\Response(response: 200, description: 'Authenticated', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                new OA\Property(property: 'abilities', type: 'array', items: new OA\Items(type: 'string')),
            ], type: 'object'),
        ])),
        new OA\Response(response: 422, description: 'Invalid credentials or MFA code', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
    ])]
    public function login(LoginRequest $request, AttemptLoginAction $attemptLogin, VerifyMfaCodeAction $verifyMfaCode): JsonResponse
    {
        $user = $attemptLogin->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->throttleKey(),
        );

        if ($user->requiresMfa()) {
            $code = $request->string('mfa_code')->toString();

            if ($code === '' || ! $verifyMfaCode->handle($user, $code)) {
                throw ValidationException::withMessages(['mfa_code' => __('A valid MFA code is required for this account.')]);
            }
        }

        $abilities = $this->abilitiesFor($user);
        $token = $user->createToken($request->string('device_name')->toString(), $abilities);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
                'abilities' => $abilities,
            ],
        ]);
    }

    #[OA\Post(path: '/auth/logout', summary: 'Revoke the current API token', security: [['sanctum' => []]], tags: ['Auth'], responses: [new OA\Response(response: 200, description: 'Logged out')])]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => ['message' => 'Logged out.']]);
    }

    #[OA\Get(path: '/auth/me', summary: 'Get the authenticated user\'s profile', security: [['sanctum' => []]], tags: ['Auth'], responses: [new OA\Response(response: 200, description: 'Current user', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
    ]))])]
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    /**
     * Token abilities are derived from the user's actual Spatie permissions
     * rather than granted wholesale — FR-API-004 requires tokens scoped by
     * ability, so a token can never do more via the API than the issuing
     * user could already do in the app.
     *
     * @return list<string>
     */
    private function abilitiesFor(User $user): array
    {
        $permissionNames = $user->getAllPermissions()->pluck('name');

        $abilities = collect(self::PERMISSION_TO_ABILITY)
            ->filter(fn (string $ability, string $permission) => $permissionNames->contains($permission))
            ->values()
            ->all();

        $abilities[] = 'rooms:read';

        return array_values(array_unique($abilities));
    }
}
