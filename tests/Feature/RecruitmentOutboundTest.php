<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Recruitment\RecruitmentApiClient;
use App\Services\RecruitmentDocumentService;
use App\Support\RecruitmentOutboundConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integrasi keluar ALIM → aplikasi recruitment: konfigurasi, client, dan
 * pengaturan dari halaman admin.
 */
class RecruitmentOutboundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.recruitment_api.url' => 'https://recruitment.test/api',
            'services.recruitment_api.token' => 'outbound-token',
        ]);

        Cache::forget('system_settings_map');
    }

    public function test_base_url_is_normalized_without_api_suffix(): void
    {
        $this->assertSame('https://recruitment.test', RecruitmentOutboundConfig::normalizeBaseUrl('https://recruitment.test/api/'));
        $this->assertSame('https://recruitment.test', RecruitmentOutboundConfig::normalizeBaseUrl('https://recruitment.test/'));
    }

    public function test_connection_uses_health_endpoint_and_bearer_token(): void
    {
        Http::fake([
            'https://recruitment.test/api/health' => Http::response(['status' => 'ok'], 200),
        ]);

        $result = app(RecruitmentApiClient::class)->testConnection();

        $this->assertTrue($result['ok']);

        Http::assertSent(fn ($request) => $request->url() === 'https://recruitment.test/api/health'
            && $request->hasHeader('Authorization', 'Bearer outbound-token'));
    }

    public function test_documents_are_fetched_and_unwrapped(): void
    {
        Http::fake([
            'https://recruitment.test/api/profiles/5/documents' => Http::response([
                'data' => [
                    ['id' => 1, 'jenis' => 'cv', 'url' => 'https://files.test/cv.pdf'],
                ],
            ], 200),
        ]);

        $documents = app(RecruitmentApiClient::class)->documents(5);

        $this->assertCount(1, $documents);
        $this->assertSame('cv', $documents[0]['jenis']);
    }

    public function test_applications_query_is_forwarded(): void
    {
        Http::fake([
            'https://recruitment.test/api/applications*' => Http::response([
                'data' => [['id' => 10, 'status' => 'baru']],
            ], 200),
        ]);

        $applications = app(RecruitmentApiClient::class)->applications([
            'status' => 'baru',
            'page' => 2,
        ]);

        $this->assertCount(1, $applications);
        $this->assertSame(10, $applications[0]['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/applications')
                && str_contains($request->url(), 'status=baru')
                && str_contains($request->url(), 'page=2');
        });
    }

    public function test_failed_request_returns_empty_list(): void
    {
        Http::fake([
            'https://recruitment.test/api/applications*' => Http::response(['message' => 'error'], 500),
        ]);

        $this->assertSame([], app(RecruitmentApiClient::class)->applications());
    }

    public function test_existing_document_service_uses_managed_outbound_config(): void
    {
        RecruitmentOutboundConfig::storeUrl('https://recruitment.test');
        RecruitmentOutboundConfig::storeToken('managed-token');
        Cache::forget('system_settings_map');

        Http::fake([
            'https://recruitment.test/api/profiles/7/documents' => Http::response([
                'data' => [['id' => 99, 'jenis' => 'ijazah']],
            ], 200),
        ]);

        $documents = RecruitmentDocumentService::getDocuments(7);

        $this->assertCount(1, $documents);

        Http::assertSent(fn ($request) => $request->url() === 'https://recruitment.test/api/profiles/7/documents'
            && $request->hasHeader('Authorization', 'Bearer managed-token'));
    }

    public function test_admin_can_save_outbound_url_and_encrypted_token(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'is_system_admin' => true]);
        $url = "/{$admin->id}/sa/system-settings";

        Http::fake([
            'https://recruitment.test/api/health' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->actingAs($admin)->from($url)->post($url, [
            'recruitment_tab' => '1',
            'recruitment_api_enabled' => '1',
            'recruitment_api_header' => 'X-ALIM-RECRUITMENT-KEY',
            'recruitment_api_allowed_ips' => '',
            'recruitment_outbound_url' => 'https://recruitment.test/api',
            'recruitment_outbound_token' => 'managed-outbound-token',
            'test_recruitment_connection' => '1',
        ]);

        $response->assertRedirect($url);
        $response->assertSessionHas('success');

        $this->assertSame('https://recruitment.test/api', SystemSetting::get('recruitment_outbound_url'));

        $rawToken = (string) SystemSetting::get('recruitment_outbound_token');
        $this->assertStringStartsWith('enc:v1:', $rawToken);
        $this->assertSame('managed-outbound-token', RecruitmentOutboundConfig::token());
    }
}
