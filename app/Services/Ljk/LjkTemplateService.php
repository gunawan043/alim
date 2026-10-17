<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkTemplate;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pembangun geometri template LJK (satuan milimeter) + registrasi template berversi.
 *
 * Kontrak geometri (disimpan di ljk_templates.geometry):
 * {
 *   version, dpi, paper{width_mm,height_mm}, columns, row_height_mm,
 *   box{width_mm,height_mm,gap_mm},
 *   pages: [{ index, fiducials[], qr{}, questions[] }],
 *   questions: [flat: { nomor, type, manual, page, column, row, x_mm, y_mm,
 *                       box{}, options[{label,x_mm,y_mm,w_mm,h_mm}] }],
 *   digest
 * }
 */
class LjkTemplateService
{
    /**
     * Bangun geometri dari daftar butir.
     *
     * @param  array<int, array{nomor:int,jenis:string,options?:int}>  $specs
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function buildGeometry(array $specs, array $overrides = []): array
    {
        $cfg = array_replace_recursive(config('ljk.template', []), $overrides);

        $paper = $cfg['paper'];
        $pageW = (float) $paper['width_mm'];
        $pageH = (float) $paper['height_mm'];
        $margin = $cfg['margin_mm'];
        $fiducial = $cfg['fiducial'];
        $qrConf = $cfg['qr'];
        $box = $cfg['box'];
        $rowH = (float) $cfg['row_height_mm'];
        $colGap = (float) $cfg['column_gap_mm'];
        $maxColumns = (int) $cfg['max_columns'];
        $headerH = (float) $cfg['header_height_mm'];
        $footerMargin = (float) $cfg['footer_margin_mm'];

        $usableW = $pageW - $margin['left'] - $margin['right'];
        $usableH = $pageH - $headerH - $footerMargin;

        $capacityPerPage = max(1, (int) floor($usableH / $rowH));

        // Rombak butir menjadi baris (manual/uraian = 2 baris).
        $rows = [];
        foreach (array_values($specs) as $spec) {
            $manual = in_array($spec['jenis'], config('ljk.grading.manual_types', ['isian', 'uraian']), true);
            $rows[] = ['spec' => $spec, 'manual' => $manual, 'span' => $manual ? 2 : 1];
        }

        // Kolom = 4 soal sebaris bila muat; jumlah kolom dihitung otomatis
        // dari jumlah opsi terbanyak (5 opsi → 3 kolom) agar blok nomor +
        // semua kotak tidak menabrak kolom sebelah.
        $maxOptions = max(2, min((int) $this->maxOptions($specs), (int) $cfg['max_options']));
        $labelW = 8.0;
        $minColumnWidth = $labelW
            + $maxOptions * (float) $box['width_mm']
            + ($maxOptions - 1) * (float) $box['gap_mm']
            + 2.0;
        $maxColumnsByWidth = max(1, (int) floor(($usableW + $colGap) / ($minColumnWidth + $colGap)));

        $preferredColumns = max(1, (int) ($cfg['preferred_columns'] ?? 4));
        $neededColumns = max(1, (int) ceil(count($rows) / $capacityPerPage));
        $columns = max(1, min($maxColumns, $maxColumnsByWidth, max($preferredColumns, $neededColumns)));
        $columnWidth = ($usableW - ($columns - 1) * $colGap) / $columns;

        // Distribusi kolom-major: nomor berurut ke bawah pada satu kolom,
        // lalu lanjut ke kolom berikutnya (kiri → kanan). Contoh 23 soal:
        // kolom 1 = 1–6, kolom 2 = 7–12, kolom 3 = 13–18, kolom 4 = 19–23.
        $rowsPerColumn = max(1, (int) ceil(count($rows) / $columns));

        $pages = [];
        $flat = [];
        $pageIndex = 1;
        $columnIndex = 1;
        $rowCursor = 0;

        $makePage = function (int $index) use ($pageW, $pageH, $margin, $fiducial, $qrConf): array {
            $size = (float) $fiducial['size_mm'];

            return [
                'index' => $index,
                'fiducials' => [
                    ['id' => 'TL', 'x_mm' => round($margin['left'] - 3.0, 2), 'y_mm' => round($margin['top'] - 4.0, 2), 'size_mm' => $size],
                    ['id' => 'TR', 'x_mm' => round($pageW - $margin['right'] - $size + 3.0, 2), 'y_mm' => round($margin['top'] - 4.0, 2), 'size_mm' => $size],
                    ['id' => 'BL', 'x_mm' => round($margin['left'] - 3.0, 2), 'y_mm' => round($pageH - $margin['bottom'] - $size + 3.0, 2), 'size_mm' => $size],
                    ['id' => 'BR', 'x_mm' => round($pageW - $margin['right'] - $size + 3.0, 2), 'y_mm' => round($pageH - $margin['bottom'] - $size + 3.0, 2), 'size_mm' => $size],
                ],
                'qr' => [
                    // Digeser ke kiri agar tidak menimpa penanda TR (8 mm).
                    'x_mm' => round($pageW - $margin['right'] - (float) $qrConf['size_mm'] - 8.0, 2),
                    'y_mm' => round((float) $qrConf['offset_top_mm'], 2),
                    'size_mm' => (float) $qrConf['size_mm'],
                ],
                // Data strip mesin (32 sel) — identitas lembar & halaman.
                'strip' => [
                    'x_mm' => round($margin['left'] - 3.0, 2),
                    'y_mm' => round($pageH - $margin['bottom'] + 5.0, 2),
                    'cell_mm' => 2.0,
                    'count' => 32,
                    'bit_order' => 'msb_first',
                ],
                'questions' => [],
            ];
        };

        $pages[$pageIndex] = $makePage($pageIndex);

        foreach ($rows as $row) {
            // Kolom penuh (kuota merata / kapasitas halaman) → kolom berikutnya.
            if ($rowCursor >= $rowsPerColumn || $rowCursor >= $capacityPerPage) {
                $rowCursor = 0;
                $columnIndex++;
                if ($columnIndex > $columns) {
                    $columnIndex = 1;
                    $pageIndex++;
                    $pages[$pageIndex] = $makePage($pageIndex);
                }
            }

            /** @var array{nomor:int,jenis:string,options?:int} $spec */
            $spec = $row['spec'];

            $colX = (float) $margin['left'] + ($columnIndex - 1) * ($columnWidth + $colGap);
            $rowY = $headerH + $rowCursor * $rowH;

            $question = [
                'nomor' => (int) $spec['nomor'],
                'type' => (string) $spec['jenis'],
                'manual' => $row['manual'],
                'page' => $pageIndex,
                'column' => $columnIndex,
                'row' => $rowCursor + 1,
                'x_mm' => round($colX, 2),
                'y_mm' => round($rowY, 2),
            ];

            if ($row['manual']) {
                $question['w_mm'] = round($columnWidth, 2);
                $question['h_mm'] = round($rowH * $row['span'] - 1.6, 2);
                $question['options'] = [];
            } else {
                $optionCount = max(2, min((int) ($spec['options'] ?? 5), (int) $cfg['max_options']));
                $boxesW = $optionCount * (float) $box['width_mm'] + ($optionCount - 1) * (float) $box['gap_mm'];
                $offsetX = $labelW; // blok kiri-rata agar kolom sejajar lurus
                if ($offsetX + $boxesW > $columnWidth) {
                    $offsetX = max(0.0, $columnWidth - $boxesW);
                }

                $question['options'] = [];
                for ($i = 0; $i < $optionCount; $i++) {
                    $question['options'][] = [
                        'label' => chr(65 + $i),
                        'x_mm' => round($colX + $offsetX + $i * ((float) $box['width_mm'] + (float) $box['gap_mm']), 2),
                        'y_mm' => round($rowY, 2),
                        'w_mm' => (float) $box['width_mm'],
                        'h_mm' => (float) $box['height_mm'],
                    ];
                }
            }

            $pages[$pageIndex]['questions'][] = $question;
            $flat[] = $question;
            $rowCursor += $row['span'];
        }

        $geometry = [
            'version' => 1,
            'dpi' => (int) config('ljk.canonical_dpi', 200),
            'paper' => ['width_mm' => $pageW, 'height_mm' => $pageH],
            'columns' => $columns,
            'row_height_mm' => $rowH,
            'box' => $box,
            'max_options' => max(2, min((int) ($this->maxOptions($specs)), (int) config('ljk.template.max_options', 5))),
            'header_height_mm' => $headerH,
            'pages' => array_values($pages),
            'questions' => $flat,
        ];

        $geometry['digest'] = $this->digestGeometry($geometry);

        return $geometry;
    }

    /**
     * @param  array<int, array{nomor:int,jenis:string,options?:int}>  $specs
     */
    protected function maxOptions(array $specs): int
    {
        $max = 2;
        foreach ($specs as $spec) {
            $max = max($max, (int) ($spec['options'] ?? 2));
        }

        return $max;
    }

    /**
     * Sidik jari geometri — mendeteksi perubahan layout template.
     *
     * @param  array<string, mixed>  $geometry
     */
    public function digestGeometry(array $geometry): string
    {
        $copy = $geometry;
        unset($copy['digest']);

        // Tanpa JSON_PRESERVE_ZERO_FRACTION: nilai 210.0 dan 210 (hasil
        // pembacaan ulang JSON dari database) harus menghasilkan digest sama.
        return hash('sha256', json_encode($this->normalizeNumbers($copy), JSON_UNESCAPED_SLASHES));
    }

    /**
     * Normalkan angka (float/int) agar stabil setelah putaran JSON.
     *
     * @param  mixed  $value
     * @return mixed
     */
    private function normalizeNumbers($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->normalizeNumbers($v);
            }

            return $out;
        }

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * Daftarkan (atau pakai ulang) template berdasarkan fingerprint layout.
     *
     * @param  array<int, array{nomor:int,jenis:string,options?:int}>  $specs
     */
    public function registerTemplate(array $specs, ?string $createdBy = null, array $overrides = []): LjkTemplate
    {
        $geometry = $this->buildGeometry($specs, $overrides);
        $columns = (int) $geometry['columns'];
        $questions = count($specs);
        $maxOptions = (int) $geometry['max_options'];
        $fingerprint = substr($geometry['digest'], 0, 10);

        $code = strtoupper(sprintf('LJK-A4-C%d-Q%d-O%d-%s', $columns, $questions, $maxOptions, $fingerprint));

        $existing = LjkTemplate::query()->where('code', $code)->orderByDesc('version')->first();
        if ($existing) {
            return $existing;
        }

        return LjkTemplate::create([
            'code' => $code,
            'version' => 1,
            'name' => sprintf('LJK %d soal · %d kolom', $questions, $columns),
            'paper' => 'A4',
            'orientation' => 'portrait',
            'columns' => $columns,
            'box_width_mm' => (float) $geometry['box']['width_mm'],
            'box_height_mm' => (float) $geometry['box']['height_mm'],
            'row_height_mm' => (float) $geometry['row_height_mm'],
            'geometry' => $geometry,
            'is_active' => true,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Validasi geometri sebelum dipakai mesin OMR.
     *
     * @param  array<string, mixed>  $geometry
     */
    public function assertValidGeometry(array $geometry): void
    {
        if (! isset($geometry['pages']) || ! is_array($geometry['pages']) || $geometry['pages'] === []) {
            throw new RuntimeException('Geometri template tidak memiliki halaman.');
        }

        foreach ($geometry['pages'] as $page) {
            if (count($page['fiducials'] ?? []) !== 4) {
                throw new RuntimeException('Setiap halaman template harus memiliki 4 penanda geometris.');
            }
        }

        if (($geometry['digest'] ?? null) !== $this->digestGeometry($geometry)) {
            throw new RuntimeException('Digest geometri template tidak cocok — template dianggap berubah.');
        }
    }

    /**
     * Spesifikasi butir dari paket soal (naskah final), tanpa menyalin isi soal.
     *
     * @return array<int, array{nomor:int,jenis:string,options:int,item_id:?string,soal_id:?string,bobot:float}>
     */
    public function specsFromItems(iterable $items, bool $shuffleQuestions = false, bool $shuffleOptions = false): array
    {
        $specs = [];

        foreach ($items as $index => $item) {
            $konten = $item->kontenFinal();
            $options = $konten['opsi'] ?? [];
            $labels = array_values(array_map(static fn ($o) => (string) ($o['label'] ?? ''), $options));

            if ($shuffleOptions && $labels !== []) {
                $labels = $this->shuffleLabels($labels);
            }

            $specs[] = [
                'nomor' => $index + 1,
                'jenis' => (string) ($konten['jenis'] ?? 'pg'),
                'options' => max(2, count($labels)),
                'item_id' => (string) $item->id,
                'soal_id' => $konten['soal_id'] ?? null,
                'bobot' => (float) ($konten['bobot'] ?? 0),
                'labels' => $labels === [] ? array_slice(range('A', 'E'), 0, 4) : $labels,
            ];
        }

        if ($shuffleQuestions) {
            $specs = $this->shuffleItems($specs);
            foreach ($specs as $i => &$spec) {
                $spec['nomor'] = $i + 1;
            }
            unset($spec);
        }

        return $specs;
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, string>
     */
    protected function shuffleLabels(array $labels): array
    {
        $shuffled = $labels;
        shuffle($shuffled);

        // Pastikan hasil berbeda bila memungkinkan.
        if ($shuffled === $labels && count($labels) > 1) {
            $first = array_shift($shuffled);
            $shuffled[] = $first;
        }

        return $shuffled;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function shuffleItems(array $items): array
    {
        $shuffled = $items;
        shuffle($shuffled);

        return $shuffled;
    }
}
