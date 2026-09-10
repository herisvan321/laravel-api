<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Handle the incoming health check request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $isHealthy = true;

        // 1. Database Connectivity Check
        $dbStart = microtime(true);
        try {
            DB::connection()->getPdo();
            $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
            $dbStatus = [
                'status' => 'healthy',
                'connection' => config('database.default'),
                'latency_ms' => $dbLatency,
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $dbStatus = [
                'status' => 'unhealthy',
                'connection' => config('database.default'),
                'error' => $e->getMessage(),
            ];
        }

        // 2. Cache Read/Write Check
        $cacheStart = microtime(true);
        try {
            $cacheKey = 'health_check_' . hrtime(true);
            Cache::put($cacheKey, 'ok', 10);
            $cachedVal = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            $cacheLatency = round((microtime(true) - $cacheStart) * 1000, 2);
            $cacheHealthy = ($cachedVal === 'ok');

            if (! $cacheHealthy) {
                $isHealthy = false;
            }

            $cacheStatus = [
                'status' => $cacheHealthy ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default'),
                'latency_ms' => $cacheLatency,
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $cacheStatus = [
                'status' => 'unhealthy',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }

        // 3. Octane & Server Engine Status
        $isOctane = (isset($_SERVER['LARAVEL_OCTANE']) && $_SERVER['LARAVEL_OCTANE'] == 1) || function_exists('frankenphp_handle_request');
        $octaneEngine = null;

        if ($isOctane) {
            if (function_exists('frankenphp_handle_request') || isset($_SERVER['FRANKENPHP_VERSION']) || isset($_SERVER['CADDY_SERVER_ADMIN_PORT'])) {
                $octaneEngine = 'frankenphp';
            } elseif (config('octane.server') === 'swoole' || (extension_loaded('swoole') && defined('SWOOLE_VERSION') && isset($_SERVER['SWOOLE_HTTP_SERVER']))) {
                $octaneEngine = 'swoole';
            } else {
                $octaneEngine = config('octane.server', env('OCTANE_SERVER', 'frankenphp'));
            }
        }

        $octaneStatus = [
            'running' => $isOctane,
            'server' => $octaneEngine,
        ];

        // 4. System & Memory Metrics
        $systemMetrics = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        $payload = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'octane' => $octaneStatus,
            'services' => [
                'database' => $dbStatus,
                'cache' => $cacheStatus,
            ],
            'system' => $systemMetrics,
        ];

        if (! $isHealthy) {
            return response()->json([
                'success' => false,
                'code' => 503,
                'message' => 'System is unhealthy',
                'data' => $payload,
            ], 503);
        }

        return ApiResponse::success($payload, 'System is healthy');
    }
}
