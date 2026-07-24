<?php

declare(strict_types=1);

namespace App\Http\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * FR-API-003: every API error, whatever its origin, is rendered through this
 * single envelope shape so API consumers only ever need to handle one error
 * contract: {"error": {"code", "message", "errors"?}}.
 *
 * Laravel's exception handler rewrites several exception types before any
 * custom render() callback sees them (Handler::prepareException()) —
 * notably AuthorizationException always arrives here already converted to
 * Symfony's AccessDeniedHttpException, and ModelNotFoundException to
 * NotFoundHttpException. Both the original and the converted type are
 * matched below so this stays correct even if that framework internal
 * changes.
 */
class ApiExceptionRenderer
{
    public function render(Throwable $e, Request $request): JsonResponse
    {
        [$status, $code, $message, $errors] = match (true) {
            $e instanceof ValidationException => [422, 'validation_error', $e->getMessage(), $e->errors()],
            $e instanceof AuthenticationException => [401, 'unauthenticated', 'Authentication is required to access this resource.', null],
            $e instanceof AuthorizationException, $e instanceof AccessDeniedHttpException => [403, 'forbidden', $e->getMessage() ?: 'This action is unauthorized.', null],
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => [404, 'not_found', 'The requested resource was not found.', null],
            $e instanceof MethodNotAllowedHttpException => [405, 'method_not_allowed', 'This HTTP method is not supported for this endpoint.', null],
            $e instanceof ThrottleRequestsException, $e instanceof TooManyRequestsHttpException => [429, 'too_many_requests', 'Too many requests. Please try again later.', null],
            default => [500, 'server_error', config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.', null],
        };

        $error = ['code' => $code, 'message' => $message];

        if ($errors !== null) {
            $error['errors'] = $errors;
        }

        return response()->json(['error' => $error], $status);
    }
}
