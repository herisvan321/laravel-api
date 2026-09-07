<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pastikan semua respons error berformat JSON (API)
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => true,
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage() ?: 'The route could not be found.',
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage() ?: 'HTTP error occurred.',
            ], $e->getStatusCode());
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error.',
            ], 500);
        });
    })->create();
