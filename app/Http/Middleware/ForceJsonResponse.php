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

            // Cek apakah konten string sudah berformat JSON
            json_decode($content);
            if (json_last_error() === JSON_ERROR_NONE && ! is_numeric($content)) {
                $response->headers->set('Content-Type', 'application/json');
            } elseif ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && ! empty($content)) {
                // Jika controller me-return string biasa (bukan JSON), bungkus menjadi JSON
                return response()->json([
                    'data' => $content,
                ], $response->getStatusCode());
            }
        }

        return $response;
    }
}
