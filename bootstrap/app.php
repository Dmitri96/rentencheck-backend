<?php

use App\Exceptions\Domain\DomainException;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust the reverse proxy (Forge's nginx) so APP_URL scheme and client IP
        // are derived from X-Forwarded-* headers. Without this, SECURE cookies and
        // HTTPS-only redirects break behind nginx-terminated TLS.
        $middleware->trustProxies(at: '*');

        // Sanctum SPA: requests from sanctum.stateful domains receive
        // session-cookie auth (CSRF protected). Bearer tokens still work
        // for non-stateful origins (third-party API consumers).
        $middleware->statefulApi();

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Domain exceptions (business rules, missing resources, etc.) map to
        // a consistent JSON envelope so controllers don't need their own
        // try/catch boilerplate.
        $exceptions->render(function (DomainException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode(),
            ], $e->httpStatus());
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'validation_failed',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'Forbidden',
                'error_code' => 'forbidden',
            ], 403);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Unauthenticated',
                'error_code' => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Resource not found',
                'error_code' => 'not_found',
            ], 404);
        });
    })->create();
