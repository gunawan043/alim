<?php

namespace Tests\Feature;

use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regresi deploy & health (Cycle 9.7).
 *
 *  - /health tetap ada di semua lingkungan, tetapi hanya mengembalikan status+time.
 *  - POST /webhook/deploy hanya terdaftar di produksi, dan pengendalinya menolak
 *    (404) di luar produksi walau route-nya terdaftar karena route cache.
 */
class DeployRouteAndHealthTest extends TestCase
{
    private function healthPayload()
    {
        return $this->getJson('/health');
    }

    public function test_health_returns_status_and_time_only(): void
    {
        $response = $this->healthPayload();

        $response->assertOk();

        // `request_id` ditambahkan RequestIdMiddleware untuk setiap JSON, bukan
        // bagian dari body /health; sisanya harus tepat status + time.
        $keys = array_keys($response->json());
        sort($keys);
        $this->assertSame(['request_id', 'status', 'time'], $keys);
        $this->assertSame('ok', $response->json('status'));
        $this->assertSame($response->headers->get('X-Request-ID'), $response->json('request_id'));
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            (string) $response->json('time')
        );
    }

    public function test_health_does_not_leak_environment_or_app_name(): void
    {
        $body = $this->healthPayload()->getContent();

        foreach (['testing', 'production', 'staging', 'local', 'APP_ENV', 'env', 'app', 'Laravel'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, (string) $body);
        }
    }

    public function test_health_route_is_registered_in_every_environment(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->uri() === 'health' && in_array('GET', $r->methods(), true));

        $this->assertCount(1, $routes);
    }

    public function test_deploy_webhook_is_not_registered_outside_production(): void
    {
        $this->assertNotSame('production', app()->environment());

        $deploy = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->uri() === 'webhook/deploy');

        $this->assertCount(0, $deploy, 'route webhook/deploy tidak boleh terdaftar di luar produksi');
    }

    public function test_deploy_webhook_is_unreachable_outside_production(): void
    {
        // Route tidak didaftarkan, jadi POST jatuh ke fallback 404 atau 405 dari
        // route lain — yang penting bukan 200/202 dan bukan rute deploy.
        $status = $this->postJson('/webhook/deploy', [])->getStatusCode();

        $this->assertContains($status, [404, 405], 'webhook deploy tidak boleh dapat diproses di luar produksi');
    }

    public function test_deploy_controller_rejects_non_production_even_if_route_existed(): void
    {
        // Daftarkan route sementara untuk membuktikan penjaga di pengendali berlaku.
        Route::post('/_probe/webhook-deploy', [DeployController::class, 'handle']);

        $this->postJson('/_probe/webhook-deploy', [], ['X-Hub-Signature-256' => 'sha256=x'])
            ->assertNotFound();
    }
}
