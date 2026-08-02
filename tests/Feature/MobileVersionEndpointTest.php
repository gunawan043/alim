<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileVersionEndpointTest extends TestCase
{
    public function test_version_endpoint_returns_app_version_payload(): void
    {
        $response = $this->getJson('/api/mobile/v1/version');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['version', 'build', 'minimum_supported', 'force_update'],
            ])
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('data.version'));
        $this->assertNotEmpty($response->json('data.build'));
    }

    public function test_build_endpoint_returns_laravel_and_php_versions(): void
    {
        $response = $this->getJson('/api/mobile/v1/build');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['git_commit', 'build_time', 'laravel_version', 'php_version', 'app_version'],
            ]);

        $this->assertSame(PHP_VERSION, $response->json('data.php_version'));
    }

    public function test_system_status_endpoint_runs_real_checks(): void
    {
        $response = $this->getJson('/api/mobile/v1/system/status');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'app_version',
                    'database' => ['healthy', 'driver'],
                    'cache' => ['healthy', 'driver'],
                    'queue' => ['healthy', 'driver'],
                    'storage' => ['healthy', 'disk'],
                ],
            ]);

        $this->assertTrue($response->json('data.database.healthy'));
    }

    public function test_health_endpoint_returns_200_when_db_healthy(): void
    {
        $response = $this->getJson('/api/mobile/v1/health');

        $response->assertOk()
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_responses_include_request_id_header_and_body(): void
    {
        $response = $this->getJson('/api/mobile/v1/version');

        $response->assertHeader('X-Request-ID');
    }

    public function test_force_update_flag_driven_by_client_version_header(): void
    {
        $response = $this->getJson('/api/mobile/v1/version', [
            'X-App-Version' => '0.0.1',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.force_update', true);
    }
}
