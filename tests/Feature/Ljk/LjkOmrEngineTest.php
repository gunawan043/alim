<?php

namespace Tests\Feature\Ljk;

use App\Services\Ljk\LjkSyntheticRenderer;
use App\Services\Ljk\LjkTemplateService;
use App\Services\Ljk\Omr\OmrEngine;
use Tests\TestCase;

/**
 * Mesin OMR: pengujian citra sintetis berlabel sebelum dataset fisik.
 */
class LjkOmrEngineTest extends TestCase
{
    /**
     * @return array<int, array{nomor:int,jenis:string,options:int}>
     */
    private function specs(int $count = 40, int $options = 4): array
    {
        $specs = [];
        for ($i = 1; $i <= $count; $i++) {
            $specs[] = ['nomor' => $i, 'jenis' => 'pg', 'options' => $options];
        }

        return $specs;
    }

    /**
     * @return array<string, mixed>
     */
    private function geometry(int $count = 40, int $options = 4): array
    {
        return app(LjkTemplateService::class)->buildGeometry($this->specs($count, $options));
    }

    private function render(array $geometry, array $content, array $opts = []): string
    {
        static $tmp = null;

        $path = sys_get_temp_dir().'/ljk-test-'.uniqid('', true).'.jpg';

        return app(LjkSyntheticRenderer::class)->renderToFile($geometry, $content, $opts + ['path' => $path]);
    }

    public function test_tata_letak_dua_kolom_kiri_1_10_kanan_11_20(): void
    {
        $geometry = $this->geometry(20, 4);

        $this->assertSame(2, $geometry['columns'], 'Template harus memakai 2 kolom sejak awal.');
        $this->assertSame(1, count($geometry['pages']));

        $byNumber = collect($geometry['questions'])->keyBy('nomor');

        // Kiri kolom 1 (no 1-10), kanan kolom 2 (no 11-20).
        foreach (range(1, 10) as $nomor) {
            $this->assertSame(1, $byNumber[$nomor]['column'], "Nomor {$nomor} harus di kolom kiri.");
        }
        foreach (range(11, 20) as $nomor) {
            $this->assertSame(2, $byNumber[$nomor]['column'], "Nomor {$nomor} harus di kolom kanan.");
        }

        // Baris pertama kiri/kanan sejajar (soal 1 & 11 di y yang sama).
        $this->assertSame($byNumber[1]['y_mm'], $byNumber[11]['y_mm']);
        $this->assertGreaterThan($byNumber[1]['x_mm'], $byNumber[11]['x_mm']);

        // Area jawaban tetap memakai lebar halaman penuh (2 kolom, bukan 1).
        $this->assertGreaterThan(80.0, (float) $byNumber[11]['x_mm']);
    }

    public function test_membaca_tanda_silang_pulpen_hitam_dan_biru(): void
    {
        $geometry = $this->geometry();

        $answers = [];
        $expected = [];
        $labels = ['A', 'B', 'C', 'D'];

        foreach (range(1, 40) as $n) {
            $label = $labels[$n % 4];
            $answers[$n] = $label;
            $expected[$n] = $label;
        }

        // Nomor 3 & 7 memakai tinta biru.
        $styles = [3 => 'x_blue', 7 => 'x_blue'];

        $path = $this->render($geometry, ['answers' => $answers, 'styles' => $styles], ['participant_seq' => 12]);
        $result = app(OmrEngine::class)->process($path, $geometry);

        $this->assertTrue($result['ok'], 'Engine gagal: '.json_encode($result['quality']));
        $this->assertSame(40, count($result['questions']));
        $this->assertTrue($result['strip']['checksum_valid'], 'Data strip tidak terbaca.');
        $this->assertSame(12, $result['strip']['participant_seq']);
        $this->assertSame(1, $result['strip']['page']);

        foreach ($result['questions'] as $question) {
            $nomor = $question['nomor'];
            $this->assertSame('terbaca', $question['status'], "Nomor {$nomor} tidak terbaca: ".json_encode($question['evidence']));
            $this->assertSame($expected[$nomor], $question['selected'], "Nomor {$nomor} salah baca.");
        }
    }

    public function test_tetap_membaca_pada_foto_miring_bayangan_dan_blur_ringan(): void
    {
        $geometry = $this->geometry(30, 5);
        $labels = ['A', 'B', 'C', 'D', 'E'];

        $answers = [];
        $expected = [];
        foreach (range(1, 30) as $n) {
            $label = $labels[($n * 2) % 5];
            $answers[$n] = $label;
            $expected[$n] = $label;
        }

        $path = $this->render($geometry, ['answers' => $answers], [
            'participant_seq' => 7,
            'rotation_deg' => 5.0,
            'perspective' => 0.12,
            'shadow' => 0.30,
            'brightness' => -18,
            'blur' => 1,
            'canvas_scale' => 1.55,
        ]);

        $result = app(OmrEngine::class)->process($path, $geometry);

        $this->assertTrue($result['ok'], 'Engine gagal: '.json_encode($result['error_code'] ?? $result['quality']));

        $readable = 0;
        foreach ($result['questions'] as $question) {
            if ($question['status'] === 'terbaca' && $question['selected'] === $expected[$question['nomor']]) {
                $readable++;
            }
        }

        $this->assertSame(30, $readable, 'Tidak semua terbaca pada foto miring: '.json_encode(array_map(
            static fn ($q) => [$q['nomor'], $q['status'], $q['selected']],
            array_filter($result['questions'], static fn ($q) => $q['status'] !== 'terbaca')
        )));
    }

    public function test_membedakan_kosong_ganda_dan_tanda_di_luar_format(): void
    {
        $geometry = $this->geometry(12, 4);

        $answers = [
            1 => 'A',
            2 => '',      // kosong
            3 => 'AB',    // ganda
            4 => 'B',
            5 => 'C',     // check
            6 => 'D',     // slash
            7 => 'A',     // scribble
            8 => 'B',     // erase
            9 => 'C',     // thin
            10 => 'D',    // touch border
            11 => '',     // dot kecil (bukan X)
            12 => 'A',    // asymmetric
        ];

        $styles = [
            5 => 'check',
            6 => 'slash',
            7 => 'scribble',
            8 => 'erase',
            9 => 'thin',
            10 => 'touch_border',
            11 => 'dot',
            12 => 'asymmetric',
        ];

        $result = app(OmrEngine::class)->process($this->render($geometry, ['answers' => $answers, 'styles' => $styles]), $geometry);
        $this->assertTrue($result['ok']);

        $byNumber = collect($result['questions'])->keyBy('nomor');

        // Kosong
        $this->assertSame('kosong', $byNumber[2]['status'], 'Nomor 2 (kosong): '.json_encode($byNumber[2]['evidence']));

        // Ganda
        $this->assertSame('ganda', $byNumber[3]['status'], 'Nomor 3 (ganda): '.json_encode($byNumber[3]['evidence']));

        // Tanda di luar format: tidak boleh diklaim terbaca dengan yakin.
        foreach ([5, 6, 7, 8, 11] as $nomor) {
            $q = $byNumber[$nomor];
            $this->assertNotSame(
                'terbaca',
                $q['status'],
                "Nomor {$nomor} (tanda luar format) keliru dibaca terbaca: ".json_encode($q['evidence'])
            );
        }

        // Tanda X yang tipis/miring/menyentuh garis tetap terbaca.
        foreach ([9, 10, 12] as $nomor) {
            $q = $byNumber[$nomor];
            $this->assertSame('terbaca', $q['status'], "Nomor {$nomor} (X tidak sempurna) harus terbaca: ".json_encode($q['evidence']));
        }
    }

    public function test_menolak_gambar_buram_gelap_dan_terpotong(): void
    {
        $geometry = $this->geometry(10, 4);

        $blur = app(OmrEngine::class)->process($this->render($geometry, ['answers' => [1 => 'A']], ['blur' => 10]), $geometry);
        $this->assertFalse($blur['ok']);
        $this->assertSame('blur_too_high', $blur['error_code']);

        $dark = app(OmrEngine::class)->process($this->render($geometry, ['answers' => [1 => 'A']], ['brightness' => -215]), $geometry);
        $this->assertFalse($dark['ok']);
        $this->assertSame('brightness_too_low', $dark['error_code']);

        $cropped = app(OmrEngine::class)->process($this->render($geometry, ['answers' => [1 => 'A']], ['crop' => 0.22]), $geometry);
        $this->assertFalse($cropped['ok'], 'Foto terpotong seharusnya ditolak: '.json_encode($cropped['quality'] ?? []));

        // Informasi kualitas tetap tersedia untuk pesan ke pengguna.
        $this->assertArrayHasKey('sharpness', $blur['quality']);
        $this->assertArrayHasKey('brightness', $dark['quality']);
    }
}
