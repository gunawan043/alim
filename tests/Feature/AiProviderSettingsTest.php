<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperAdmin\AiProviderController;
use App\Models\AiProviderSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\Ai\AiProviderService;
use App\Services\ArabicName\AiArabicNameGenerator;
use App\Services\ArabicName\Contracts\ArabicNameGeneratorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * V9 — Pengaturan provider AI multi-platform:
 * enkripsi API key, default tunggal, tes koneksi, dan pemakaian oleh generator.
 */
class AiProviderSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin AI',
            'email' => 'sa.ai@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->actingAs($this->admin);
    }

    private function makeRequest(string $method = 'GET', array $data = []): Request
    {
        $request = Request::create('/sa/ai-providers', $method, $data);
        $request->setUserResolver(fn () => $this->admin);

        return $request;
    }

    private function storeProvider(array $overrides = []): AiProviderSetting
    {
        $data = array_merge([
            'name' => 'OpenAI Utama',
            'provider' => 'openai',
            'endpoint' => 'https://api.openai.test/v1/chat/completions',
            'api_key' => 'sk-rahasia-123',
            'model' => 'gpt-4o-mini',
            'is_active' => 1,
            'is_default' => 0,
        ], $overrides);

        app(AiProviderController::class)->store($this->makeRequest('POST', $data));

        return AiProviderSetting::where('name', $data['name'])->firstOrFail();
    }

    public function test_simpan_provider_mengenkripsi_api_key_dan_default_tunggal(): void
    {
        $first = $this->storeProvider(['name' => 'Provider A', 'is_default' => 1]);
        $second = $this->storeProvider([
            'name' => 'Provider B',
            'provider' => 'groq',
            'endpoint' => 'https://api.groq.test/v1/chat/completions',
            'model' => 'llama',
            'is_default' => 1,
        ]);

        $this->assertSame('sk-rahasia-123', $first->fresh()->api_key, 'Model harus mendekripsi API key.');
        $this->assertNotSame('sk-rahasia-123', DB::table('ai_provider_settings')->where('id', $first->id)->value('api_key'), 'API key wajib terenkripsi di DB.');
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame(1, AiProviderSetting::where('is_default', true)->count());
    }

    public function test_hanya_satu_provider_yang_boleh_aktif(): void
    {
        $first = $this->storeProvider(['name' => 'Provider A']);
        $second = $this->storeProvider([
            'name' => 'Provider B',
            'provider' => 'groq',
            'endpoint' => 'https://api.groq.test/v1/chat/completions',
            'model' => 'llama',
        ]);

        $this->assertFalse($first->fresh()->is_active);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame(1, AiProviderSetting::where('is_active', true)->count());

        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/sa/ai-providers/{$first->id}/toggle")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($first->fresh()->is_active);
        $this->assertTrue($first->fresh()->is_default);
        $this->assertFalse($second->fresh()->is_active);
        $this->assertFalse($second->fresh()->is_default);
        $this->assertSame(1, AiProviderSetting::where('is_active', true)->count());
    }

    public function test_update_kosong_mempertahankan_key_dan_clear_menghapus(): void
    {
        $provider = $this->storeProvider();

        app(AiProviderController::class)->update($this->makeRequest('PUT', [
            'name' => 'OpenAI Diperbarui',
            'provider' => 'openai',
            'endpoint' => $provider->endpoint,
            'api_key' => '',
            'model' => $provider->model,
            'is_active' => 1,
        ]), $this->admin->id, $provider->id);

        $this->assertSame('sk-rahasia-123', $provider->fresh()->api_key);

        app(AiProviderController::class)->update($this->makeRequest('PUT', [
            'name' => 'OpenAI Diperbarui',
            'provider' => 'openai',
            'endpoint' => $provider->endpoint,
            'api_key' => '',
            'clear_api_key' => 1,
            'model' => $provider->model,
            'is_active' => 1,
        ]), $this->admin->id, $provider->id);

        $this->assertNull($provider->fresh()->api_key);
    }

    public function test_tes_koneksi_dan_generator_memakai_provider_db(): void
    {
        $provider = $this->storeProvider(['is_default' => 1]);
        config()->set('arabic_names.driver', 'rule');

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'محمد فوزان']]],
            ]),
        ]);

        $result = app(AiProviderService::class)->testConnection($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame('ok', $provider->fresh()->last_test_status);

        // Meski config driver = rule, provider DB aktif membuat generator memakai AI.
        $generator = app(ArabicNameGeneratorContract::class);
        $this->assertInstanceOf(AiArabicNameGenerator::class, $generator);

        $this->assertSame('محمد فوزان', $generator->generate('Muhammad Fauzan'));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization'));
    }

    public function test_fallback_ke_kaidah_saat_provider_gagal(): void
    {
        $this->storeProvider(['is_default' => 1]);

        Http::fake([
            'api.openai.test/*' => Http::response('error', 500),
        ]);

        $result = app(ArabicNameGeneratorContract::class)->generate('Muhammad Fauzan');

        $this->assertSame('محمد فوزان', $result);
    }

    public function test_provider_lokal_tidak_memerlukan_api_key(): void
    {
        $provider = $this->storeProvider([
            'name' => 'Ollama Lokal',
            'provider' => 'ollama',
            'endpoint' => 'http://127.0.0.1:11434/v1/chat/completions',
            'api_key' => '',
            'model' => 'qwen2.5',
            'is_default' => 1,
        ]);

        Http::fake([
            '127.0.0.1:11434/*' => Http::response([
                'choices' => [['message' => ['content' => 'أحمد']]],
            ]),
        ]);

        $result = app(ArabicNameGeneratorContract::class)->generate('Ahmad');

        $this->assertSame('أحمد', $result);
        $this->assertFalse($provider->hasApiKey());
        Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));
    }

    public function test_route_tes_koneksi_provider_dapat_diakses(): void
    {
        $provider = $this->storeProvider(['is_default' => 1]);

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'OK']]],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/sa/ai-providers/{$provider->id}/test")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('ok', $provider->fresh()->last_test_status);
    }

    public function test_route_update_provider_memakai_id_provider(): void
    {
        $provider = $this->storeProvider();

        $this->actingAs($this->admin)
            ->put("/{$this->admin->id}/sa/ai-providers/{$provider->id}", [
                'name' => 'OpenAI Diperbarui',
                'provider' => 'openai',
                'endpoint' => $provider->endpoint,
                'model' => $provider->model,
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('OpenAI Diperbarui', $provider->fresh()->name);
    }

    public function test_route_default_provider_memakai_id_provider(): void
    {
        $provider = $this->storeProvider();

        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/sa/ai-providers/{$provider->id}/default")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($provider->fresh()->is_default);
    }

    public function test_route_hapus_provider_memakai_id_provider(): void
    {
        $provider = $this->storeProvider();

        $this->actingAs($this->admin)
            ->delete("/{$this->admin->id}/sa/ai-providers/{$provider->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('ai_provider_settings', ['id' => $provider->id]);
    }

    public function test_route_toggle_mengaktifkan_provider_dan_default(): void
    {
        $provider = $this->storeProvider(['is_active' => 0]);
        $provider->forceFill(['is_active' => false, 'is_default' => false])->save();

        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/sa/ai-providers/{$provider->id}/toggle")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($provider->fresh()->is_active);
        $this->assertTrue($provider->fresh()->is_default);
    }

    public function test_toggle_menolak_provider_cloud_tanpa_key(): void
    {
        $provider = $this->storeProvider(['is_active' => 0, 'api_key' => null]);
        $provider->forceFill(['is_active' => false, 'is_default' => false])->save();

        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/sa/ai-providers/{$provider->id}/toggle")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($provider->fresh()->is_active);
    }

    public function test_halaman_pengaturan_ai_dapat_dirender(): void
    {
        $this->storeProvider(['is_default' => 1]);

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/sa/ai-providers")
            ->assertOk()
            ->assertSee('Pengaturan Provider AI')
            ->assertSee('OpenAI Utama');
    }

    public function test_halaman_pengaturan_ai_dan_otorisasi(): void
    {
        $provider = $this->storeProvider();

        $view = app(AiProviderController::class)->index($this->makeRequest());
        $data = $view->getData();

        $this->assertCount(1, $data['providers']);
        $this->assertSame($provider->id, $data['providers']->first()->id);
        $this->assertNotEmpty($data['presets']);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $regular = User::create([
            'name' => 'Guru Biasa', 'email' => 'guru.ai@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $regular->assignRole($role);
        $this->actingAs($regular);

        $request = Request::create('/sa/ai-providers', 'GET');
        $request->setUserResolver(fn () => $regular);

        $this->expectException(HttpException::class);
        app(AiProviderController::class)->index($request);
    }
}
