<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Services\Ai\AiDataToolService;
use App\Services\Ai\AiProviderService;
use App\Services\Ai\AiStructuredResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiStructuredResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_valid_structured_blocks(): void
    {
        $raw = json_encode([
            'blocks' => [
                ['type' => 'text', 'text' => 'Ringkasan presensi.'],
                ['type' => 'metric', 'label' => 'Hadir', 'value' => 120, 'unit' => 'santri', 'color' => 'success'],
                ['type' => 'table', 'title' => 'Nilai', 'columns' => ['Mapel', 'Rata-rata'], 'rows' => [['Fiqih', 84.2]]],
                ['type' => 'chart', 'title' => 'Presensi', 'chart_type' => 'bar', 'labels' => ['Sen', 'Sel'], 'series' => [['name' => 'Hadir', 'data' => [90, 92]]]],
                ['type' => 'source', 'label' => 'Presensi', 'tool' => 'attendance_trend'],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $result = (new AiStructuredResponse)->parse($raw);

        $this->assertTrue($result['parsed']);
        $this->assertSame('Ringkasan presensi.', $result['text']);
        $this->assertCount(5, $result['blocks']);
        $this->assertSame('metric', $result['blocks'][1]['type']);
        $this->assertSame('table', $result['blocks'][2]['type']);
        $this->assertSame('chart', $result['blocks'][3]['type']);
        $this->assertSame('source', $result['blocks'][4]['type']);
    }

    public function test_parses_json_inside_markdown_fence(): void
    {
        $raw = "```json\n{\"blocks\":[{\"type\":\"text\",\"text\":\"Halo\"}]}\n```";

        $result = (new AiStructuredResponse)->parse($raw);

        $this->assertTrue($result['parsed']);
        $this->assertSame('Halo', $result['text']);
    }

    public function test_invalid_output_falls_back_to_safe_text(): void
    {
        $result = (new AiStructuredResponse)->parse('<script>alert(1)</script> Data tidak tersedia.');

        $this->assertFalse($result['parsed']);
        $this->assertStringNotContainsString('<script>', $result['text']);
        $this->assertSame('text', $result['blocks'][0]['type']);
    }

    public function test_blocks_are_limited_and_sanitized(): void
    {
        $raw = json_encode([
            'blocks' => [[
                'type' => 'table',
                'title' => '<b>Nilai</b>',
                'columns' => ['<i>Mapel</i>', 'Nilai'],
                'rows' => [['<script>Fiqih</script>', 80]],
            ]],
        ]);

        $result = (new AiStructuredResponse)->parse($raw);

        $this->assertTrue($result['parsed']);
        $this->assertSame('Nilai', $result['blocks'][0]['title']);
        $this->assertSame('Mapel', $result['blocks'][0]['columns'][0]);
        $this->assertSame('Fiqih', $result['blocks'][0]['rows'][0][0]);
    }

    public function test_data_tools_include_attendance_and_grades(): void
    {
        $definitions = (new AiDataToolService)->definitions([
            'school_id' => null,
            'global' => false,
            'can_view_students' => false,
            'can_view_gtk' => false,
            'is_broad' => false,
        ]);

        $names = collect($definitions)->pluck('function.name');

        $this->assertTrue($names->contains('attendance_trend'));
        $this->assertTrue($names->contains('my_schedule_today'));
        $this->assertTrue($names->contains('class_schedule'));
        $this->assertTrue($names->contains('school_schedule_today'));
        $this->assertTrue($names->contains('grades_summary'));
        $this->assertTrue($names->contains('tahfidz_summary'));
        $this->assertTrue($names->contains('ljk_summary'));
    }

    public function test_deepseek_env_provider_is_available_when_configured(): void
    {
        config()->set('ai.deepseek.endpoint', 'https://api.deepseek.com/chat/completions');
        config()->set('ai.deepseek.api_key', 'test-key');
        config()->set('ai.models.flash', 'deepseek-flash');

        $provider = (new AiProviderService)->providerForQuality('flash');

        $this->assertNotNull($provider);
        $this->assertSame('deepseek', $provider->provider);
        $this->assertSame('deepseek-flash', $provider->model);
    }

    public function test_ai_usage_log_table_is_ready(): void
    {
        $this->assertTrue(\Schema::hasTable('ai_usage_logs'));
        $this->assertTrue(\Schema::hasColumn('ai_usage_logs', 'total_tokens'));
        $this->assertTrue(\Schema::hasColumn('ai_usage_logs', 'cost'));
    }
}
