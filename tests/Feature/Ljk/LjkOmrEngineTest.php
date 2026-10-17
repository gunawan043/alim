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

    public function test_tata_letak_empat_kolom_berurut_ke_bawah(): void
    {
        $geometry = $this->geometry(20, 4);

        $this->assertSame(4, $geometry['columns'], 'Lembar memuat 4 kolom soal.');
        $this->assertSame(1, count($geometry['pages']));

        $byNumber = collect($geometry['questions'])->keyBy('nomor');

        // Nomor berurut ke bawah dulu, baru lanjut ke kolom berikutnya.
        // 20 soal / 4 kolom = 5 baris per kolom.
        foreach (range(1, 5) as $nomor) {
            $this->assertSame(1, $byNumber[$nomor]['column'], "Nomor {$nomor} harus di kolom 1.");
        }
        foreach (range(6, 10) as $nomor) {
            $this->assertSame(2, $byNumber[$nomor]['column'], "Nomor {$nomor} harus di kolom 2.");
        }
        foreach (range(11, 15) as $nomor) {
            $this->assertSame(3, $byNumber[$nomor]['column'], "Nomor {$nomor} harus di kolom 3.");
        }
        foreach (range(16, 20) as $nomor) {
            $this->assertSame(4, $byNumber[$nomor]['column'], "Nomor {$nomor} harus di kolom 4.");
        }

        // Baris pertama setiap kolom sejajar: 1, 6, 11, 16.
        foreach ([1, 6, 11, 16] as $nomor) {
            $this->assertSame($byNumber[1]['y_mm'], $byNumber[$nomor]['y_mm']);
        }
        $this->assertGreaterThan($byNumber[1]['x_mm'], $byNumber[6]['x_mm']);
        $this->assertGreaterThan($byNumber[6]['x_mm'], $byNumber[11]['x_mm']);
        $this->assertGreaterThan($byNumber[11]['x_mm'], $byNumber[16]['x_mm']);

        // Dalam satu kolom: nomor berikutnya turun, x tetap.
        $this->assertSame($byNumber[1]['x_mm'], $byNumber[2]['x_mm']);
        $this->assertGreaterThan($byNumber[1]['y_mm'], $byNumber[2]['y_mm']);

        // Kotak opsi tidak menabrak kolom berikutnya.
        $q1 = $byNumber[1];
        $last = end($q1['options']);
        $this->assertLessThanOrEqual((float) $byNumber[6]['x_mm'], (float) $last['x_mm'] + (float) $last['w_mm']);

        // Opsi sampai E → jumlah kolom otomatis turun menjadi 3.
        $geometry5 = $this->geometry(20, 5);
        $this->assertSame(3, $geometry5['columns']);
    }

    public function test_ganti_jawaban_dua_tanda_kuat_selalu_ganda_tanpa_pilih_otomatis(): void
    {
        $geometry = $this->geometry(4, 4);

        // Soal 1: tanda lama (A) tetap terlihat dan tanda baru (B) lebih tebal.
        // Mesin tidak boleh memilih B otomatis hanya karena skornya lebih tinggi.
        $path = $this->render($geometry, [
            'answers' => [1 => 'AB'],
            'option_styles' => [1 => ['A' => 'thin', 'B' => 'thick']],
        ]);

        $result = app(OmrEngine::class)->process($path, $geometry);
        $this->assertTrue($result['ok'], 'Engine gagal: '.json_encode($result['quality']));

        $q = collect($result['questions'])->firstWhere('nomor', 1);
        $this->assertSame('ganda', $q['status'], 'Dua tanda kuat harus ganda: '.json_encode($q['evidence']));
        $this->assertNull($q['selected'], 'Tidak boleh ada jawaban terpilih otomatis.');
        $this->assertTrue($q['needs_review'], 'Jawaban ganda wajib diperiksa guru.');
        $this->assertEqualsCanonicalizing(['A', 'B'], $q['selected_set']);
    }

    public function test_tanda_sama_dengan_membatalkan_jawaban_x_lama(): void
    {
        $geometry = $this->geometry(8, 4);

        // Siswa tanpa penghapus: X lama ditambah "=" (X=) lalu X baru.
        $answers = [
            1 => 'A',      // hanya X= → kosong
            2 => 'AC',     // A dibatalkan, C jawaban baru
            3 => 'ABC',    // A dibatalkan, B & C dua jawaban sah → ganda
            4 => 'B',      // normal
            5 => 'AB',     // dua-duanya dibatalkan
            6 => 'AB',     // A dibatalkan, B tipis
            7 => 'AB',     // B dibatalkan, A sah
            8 => 'ABC',    // A & C dibatalkan, B sah
        ];
        $styles = [
            1 => ['A' => 'x_equals'],
            2 => ['A' => 'x_equals', 'C' => 'x'],
            3 => ['A' => 'x_equals', 'B' => 'x', 'C' => 'x'],
            4 => ['B' => 'x'],
            5 => ['A' => 'x_equals', 'B' => 'x_equals'],
            6 => ['A' => 'x_equals', 'B' => 'thin'],
            7 => ['A' => 'x', 'B' => 'x_equals'],
            8 => ['A' => 'x_equals', 'B' => 'x', 'C' => 'x_equals'],
        ];

        $result = app(OmrEngine::class)->process($this->render($geometry, [
            'answers' => $answers,
            'option_styles' => $styles,
        ]), $geometry);

        $this->assertTrue($result['ok'], 'Engine gagal: '.json_encode($result['quality']));
        $byNumber = collect($result['questions'])->keyBy('nomor');

        $this->assertSame('kosong', $byNumber[1]['status'], 'X= saja harus kosong: '.json_encode($byNumber[1]['opsi']));
        $this->assertFalse((bool) $byNumber[1]['needs_review']);
        $this->assertTrue((bool) ($byNumber[1]['opsi']['A']['cancel_equals'] ?? false), 'X= harus terdeteksi sebagai pembatalan.');

        $this->assertSame('terbaca', $byNumber[2]['status']);
        $this->assertSame('C', $byNumber[2]['selected']);

        $this->assertSame('ganda', $byNumber[3]['status'], 'Dua X sah harus ganda: '.json_encode($byNumber[3]['evidence']));
        $this->assertNull($byNumber[3]['selected']);
        $this->assertTrue((bool) $byNumber[3]['needs_review']);

        $this->assertSame('terbaca', $byNumber[4]['status']);
        $this->assertSame('B', $byNumber[4]['selected']);

        $this->assertSame('kosong', $byNumber[5]['status'], 'Dua X= harus kosong.');
        $this->assertSame('terbaca', $byNumber[7]['status']);
        $this->assertSame('A', $byNumber[7]['selected']);
        $this->assertSame('terbaca', $byNumber[8]['status']);
        $this->assertSame('B', $byNumber[8]['selected']);
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
