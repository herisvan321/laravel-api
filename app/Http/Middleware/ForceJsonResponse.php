<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Paksa header Accept menjadi application/json
        // Ini memastikan fitur auth, validasi, dan internal Laravel selalu merespons dalam mode JSON (API)
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // 2. Jika respons bukan JsonResponse, BinaryFileResponse, atau StreamedResponse (misal string biasa)
        if (! $response instanceof JsonResponse && ! $response instanceof BinaryFileResponse && ! $response instanceof StreamedResponse) {
            $content = $response->getContent();

            if (is_string($content) && $content !== '') {
                $trimmed = trim($content);
                $firstChar = $trimmed[0] ?? '';

                // Gunakan native json_validate (PHP 8.3+) tanpa alokasi memori json_decode
                if (($firstChar === '{' || $firstChar === '[') && json_validate($trimmed)) {
                    $response->headers->set('Content-Type', 'application/json');
                } elseif ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    // Jika controller me-return string biasa (bukan JSON), bungkus menjadi JSON standar
                    return \App\Http\Responses\ApiResponse::success($content, 'Success', $response->getStatusCode());
                }
            }
        }

        return $response;
    }
}
