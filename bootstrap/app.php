<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

use App\Http\Responses\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Paksa semua request dan respons agar berformat JSON
        $middleware->prepend(\App\Http\Middleware\ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 1. Selalu render error sebagai JSON (standar API)
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => true,
        );

        // 2. Format autentikasi gagal (401 Unauthorized)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return ApiResponse::error('Unauthenticated.', 401);
        });

        // 3. Format validasi input gagal (422 Unprocessable Content)
        $exceptions->render(function (ValidationException $e, Request $request) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        });

        // 4. Format route / resource tidak ditemukan (404 Not Found)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return ApiResponse::error($e->getMessage() ?: 'The route could not be found.', 404);
        });

        // 5. Format HTTP Exception lainnya (405 Method Not Allowed, 403 Forbidden, 429 Throttle, dsb.)
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            return ApiResponse::error($e->getMessage() ?: 'HTTP error occurred.', $e->getStatusCode());
        });

        // 6. Format Internal Server Error (500)
        $exceptions->render(function (Throwable $e, Request $request) {
            return ApiResponse::error(
                config('app.debug') ? $e->getMessage() : 'Internal server error.',
                500
            );
        });
    })->create();
