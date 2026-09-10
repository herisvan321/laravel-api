<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_healthy_status_and_metrics(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'code' => 200,
                'message' => 'System is healthy',
                'data' => [
                    'status' => 'healthy',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'code',
                'message',
                'data' => [
                    'status',
                    'timestamp',
                    'octane' => [
                        'running',
                        'server',
                    ],
                    'services' => [
                        'database' => [
                            'status',
                            'connection',
                            'latency_ms',
                        ],
                        'cache' => [
                            'status',
                            'driver',
                            'latency_ms',
                        ],
                    ],
                    'system' => [
                        'php_version',
                        'laravel_version',
                        'environment',
                        'memory_usage_mb',
                        'memory_peak_mb',
                    ],
                ],
            ]);
    }

    public function test_health_check_detects_octane_runtime_when_present(): void
    {
        $_SERVER['LARAVEL_OCTANE'] = '1';

        try {
            $response = $this->getJson('/api/health');

            $response->assertStatus(200)
                ->assertJsonPath('data.octane.running', true)
                ->assertJsonPath('data.octane.server', 'swoole');
        } finally {
            unset($_SERVER['LARAVEL_OCTANE']);
        }
    }
}
