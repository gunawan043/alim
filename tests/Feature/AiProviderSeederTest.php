<?php

namespace Tests\Feature;

use App\Models\AiProviderSetting;
use App\Models\User;
use Database\Seeders\AiProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiProviderSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_provider_menampilkan_katalog_setelah_seeder(): void
    {
        $admin = User::create([
            'name' => 'Super Admin Katalog', 'email' => 'sa.katalog@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->seed(AiProviderSeeder::class);

        $this->actingAs($admin)
            ->get("/{$admin->id}/sa/ai-providers")
            ->assertOk()
            ->assertSee('OpenRouter — Free Router')
            ->assertSee('DeepSeek V4 Flash')
            ->assertSee('Gratis');
    }

    public function test_seeder_mengisi_katalog_tanpa_menimpa_key_dan_status(): void
    {
        $existing = AiProviderSetting::create([
            'id' => (string) Str::uuid(),
            'name' => 'OpenRouter',
            'provider' => 'openrouter',
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'model' => 'deepseek/deepseek-v4-flash',
            'api_key' => 'sk-existing-secret',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->seed(AiProviderSeeder::class);
        $firstCount = AiProviderSetting::count();

        $this->assertGreaterThanOrEqual(30, $firstCount);

        $existing->refresh();
        $this->assertSame('sk-existing-secret', $existing->api_key);
        $this->assertTrue($existing->is_active);
        $this->assertTrue($existing->is_default);
        $this->assertSame('openrouter', $existing->extra['key_group']);

        $newRows = AiProviderSetting::where('id', '!=', $existing->id)->get();
        $this->assertNotEmpty($newRows);
        $this->assertTrue($newRows->every(fn ($row) => ! $row->is_active && ! $row->is_default && $row->api_key === null));

        $freeRouter = AiProviderSetting::where('model', 'openrouter/free')->firstOrFail();
        $this->assertSame('free', $freeRouter->extra['tier']);
        $this->assertNull($freeRouter->api_key);
        $this->assertTrue($freeRouter->hasApiKey());
        $this->assertSame('sk-existing-secret', $freeRouter->effectiveApiKey());

        $this->seed(AiProviderSeeder::class);
        $this->assertSame($firstCount, AiProviderSetting::count(), 'Seeder harus idempoten.');
    }
}
