<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// use Illuminate\Http\Middleware\HandleCors;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Arr;
// use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            // 'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
        // ✅ Add this to enable CORS globally
        // $middleware->append(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Validation errors
        $exceptions->render(function (ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        });

        // Unauthenticated
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated Request!.',
            ], 401);
        });

        // Unauthorized (role / forbidden)
        $exceptions->render(function (AuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Access denied.',
            ], 403);
        });

        // JWT token expired
        $exceptions->render(function (TokenExpiredException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired.',
            ], 401);
        });

        // JWT token invalid
        $exceptions->render(function (TokenInvalidException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid.',
            ], 401);
        });

        // JWT token missing
        $exceptions->render(function (JWTException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided.',
            ], 401);
        });

        // Model not found
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            $model = class_basename($e->getModel());
            return response()->json([
                'success' => false,
                'message' => "{$model} not found.",
            ], 404);
        });

        // Route not found
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found.',
            ], 404);
        });

        // Method not allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed.',
            ], 405);
        });

        // Fallback / Server errors
        $exceptions->render(function (Throwable $e, $request) {
            $payload = [
                'success' => false,
                'message' => 'Server Error',
            ];

            if (config('app.debug')) {
                $payload['exception'] = get_class($e);
                $payload['message']   = $e->getMessage();
                $payload['trace']     = collect($e->getTrace())
                    ->map(fn($t) => Arr::only($t, ['file', 'line', 'function']))
                    ->take(10)
                    ->values();
            }

            return response()->json($payload, 500);
        });
    })
    ->create();
