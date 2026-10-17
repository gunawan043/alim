<?php

namespace Tests\Feature;

use App\Services\ArabicName\AiArabicNameGenerator;
use App\Services\ArabicName\ArabicNameGenerator;
use App\Services\ArabicName\Contracts\ArabicNameGeneratorContract;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * V7 — Generator nama Arab via AI (opsional) dengan fallback kaidah transliterasi.
 */
class ArabicNameAiGeneratorTest extends TestCase
{
    private function configureAi(): void
    {
        config()->set('arabic_names.driver', 'ai');
        config()->set('arabic_names.ai.key', 'test-key');
        config()->set('arabic_names.ai.endpoint', 'https://api.openai.test/v1/chat/completions');
        config()->set('arabic_names.ai.model', 'test-model');
        config()->set('arabic_names.ai.timeout', 5);
    }

    public function test_ai_sukses_mengembalikan_teks_arab(): void
    {
        $this->configureAi();

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'محمد فوزان']],
                ],
            ]),
        ]);

        $generator = app(AiArabicNameGenerator::class);
        $result = $generator->generate('Muhammad Fauzan');

        $this->assertSame('محمد فوزان', $result);
        $this->assertSame('ai', $generator->driver());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.test/v1/chat/completions'
                && $request['model'] === 'test-model'
                && $request['messages'][1]['content'] === 'Muhammad Fauzan'
                && str_contains($request['messages'][0]['content'], 'PELAFALAN');
        });
    }

    public function test_ai_membersihkan_penjelasan_dan_huruf_latin(): void
    {
        $this->configureAi();

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => "محمد فوزان (Muhammad Fauzan)\nCatatan: ejaan baku."]],
                ],
            ]),
        ]);

        $result = app(AiArabicNameGenerator::class)->generate('Muhammad Fauzan');

        $this->assertSame('محمد فوزان', $result);
        $this->assertStringNotContainsString('Muhammad', $result);
    }

    public function test_ai_gagal_http_fallback_ke_kaidah(): void
    {
        $this->configureAi();

        Http::fake([
            'api.openai.test/*' => Http::response('upstream error', 500),
        ]);

        $result = app(AiArabicNameGenerator::class)->generate('Muhammad Fauzan');

        $this->assertSame('محمد فوزان', $result, 'Fallback kamus harus dipakai saat AI gagal.');
    }

    public function test_ai_tanpa_kunci_tetap_dipakai_untuk_endpoint_lokal(): void
    {
        $this->configureAi();
        config()->set('arabic_names.ai.key', null);

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'أحمد']]],
            ]),
        ]);

        $result = app(AiArabicNameGenerator::class)->generate('Ahmad');

        $this->assertSame('أحمد', $result);
        Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));
    }

    public function test_endpoint_kosong_fallback_ke_kaidah(): void
    {
        config()->set('arabic_names.driver', 'ai');
        config()->set('arabic_names.ai.endpoint', '');

        Http::fake();

        $result = app(AiArabicNameGenerator::class)->generate('Ahmad');

        $this->assertSame('أحمد', $result);
        Http::assertNothingSent();
    }

    public function test_ai_output_bukan_arab_fallback_ke_kaidah(): void
    {
        $this->configureAi();

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Sorry, cannot process that name.']],
                ],
            ]),
        ]);

        $result = app(AiArabicNameGenerator::class)->generate('Ahmad');

        $this->assertSame('أحمد', $result);
    }

    public function test_binding_kontrak_memilih_driver_sesuai_config(): void
    {
        config()->set('arabic_names.driver', 'rule');
        $this->assertInstanceOf(
            ArabicNameGenerator::class,
            app(ArabicNameGeneratorContract::class)
        );

        config()->set('arabic_names.driver', 'ai');
        config()->set('arabic_names.ai.key', 'test-key');
        $this->assertInstanceOf(
            AiArabicNameGenerator::class,
            app(ArabicNameGeneratorContract::class)
        );
    }
}
