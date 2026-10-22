<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiWebService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiWebSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.web_search.provider' => 'duckduckgo',
            'ai.web_search.cache_minutes' => 0,
            'ai.web_search.timeout' => 5,
        ]);
    }

    public function test_pencarian_duckduckgo_mengembalikan_judul_url_dan_snippet(): void
    {
        Http::fake([
            'html.duckduckgo.com/*' => Http::response($this->searchHtml(), 200),
        ]);

        $result = app(AiWebService::class)->search('berita pendidikan', 5);

        $this->assertTrue($result['ok'] ?? false);
        $this->assertSame('duckduckgo', $result['provider'] ?? null);
        $this->assertCount(1, $result['results'] ?? []);
        $this->assertSame('Contoh Berita Pendidikan', $result['results'][0]['title']);
        $this->assertSame('https://example.com/berita', $result['results'][0]['url']);
        $this->assertStringContainsString('Ringkasan berita', $result['results'][0]['snippet']);
    }

    public function test_read_url_hanya_membaca_url_hasil_pencarian(): void
    {
        Http::fake([
            'html.duckduckgo.com/*' => Http::response($this->searchHtml(), 200),
            'https://example.com/*' => Http::response(
                '<html><head><title>Halaman Contoh</title></head><body><p>Isi lengkap halaman.</p></body></html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            ),
        ]);

        $service = app(AiWebService::class);
        $service->search('berita pendidikan', 5);

        $page = $service->readUrl('https://example.com/berita');

        $this->assertTrue($page['ok'] ?? false);
        $this->assertSame('Halaman Contoh', $page['title'] ?? null);
        $this->assertStringContainsString('Isi lengkap halaman', $page['text'] ?? '');
    }

    public function test_read_url_menolak_url_di_luar_hasil_pencarian_dan_ip_privat(): void
    {
        $service = app(AiWebService::class);

        $this->assertArrayHasKey('error', $service->readUrl('https://example.com/berita'));
        $this->assertArrayHasKey('error', $service->readUrl('http://127.0.0.1/admin'));
    }

    public function test_tool_definitions_menyertakan_pencarian_web(): void
    {
        $names = collect(app(AiWebService::class)->definitions())
            ->pluck('function.name')
            ->all();

        $this->assertContains('web_search', $names);
        $this->assertContains('read_url', $names);
    }

    private function searchHtml(): string
    {
        return <<<'HTML'
        <html><body>
            <div class="result">
                <a class="result__a" href="//duckduckgo.com/l/?uddg=https%3A%2F%2Fexample.com%2Fberita">Contoh Berita Pendidikan</a>
                <a class="result__snippet">Ringkasan berita pendidikan terbaru.</a>
            </div>
        </body></html>
        HTML;
    }
}
