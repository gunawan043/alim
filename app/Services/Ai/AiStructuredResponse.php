<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

/**
 * Parser & validator blok jawaban AI.
 *
 * AI hanya boleh mengembalikan JSON dengan blok text/table/chart/metric/source.
 * Tidak ada HTML/JS bebas yang dirender ke browser.
 */
class AiStructuredResponse
{
    public const TYPES = ['text', 'metric', 'table', 'chart', 'source'];

    public const CHART_TYPES = ['bar', 'line', 'area', 'pie', 'donut'];

    /**
     * Istilah internal yang tidak boleh muncul di jawaban pengguna.
     * Diganti dengan label manusiawi agar nama tabel/kolom tidak bocor.
     */
    private const INTERNAL_TERMS = [
        'student_attendances' => 'data presensi',
        'admin_nilai_sumatif' => 'data nilai',
        'raport_registrations' => 'data rapor',
        'teacher_admin_books' => 'buku nilai',
        'tahfidz_setorans' => 'data setoran tahfidz',
        'tahfidz_evaluations' => 'data evaluasi tahfidz',
        'tahfidz_group_members' => 'data anggota tahfidz',
        'tahfidz_student_targets' => 'data target tahfidz',
        'ljk_exams' => 'data ujian LJK',
        'ljk_participants' => 'data peserta LJK',
        'ljk_sheets' => 'data lembar LJK',
        'ljk_scans' => 'data scan LJK',
        'ljk_sync_logs' => 'data sinkronisasi LJK',
        'by_status' => 'ringkasan status',
        'subjects_active' => 'mata pelajaran aktif',
        'academic_years' => 'tahun ajaran',
        'students_total' => 'total santri',
        'students_active' => 'santri aktif',
        'gtk_active' => 'GTK aktif',
        'rombel_active' => 'rombel aktif',
        'attendance_trend' => 'data presensi',
        'attendance_summary' => 'ringkasan presensi',
        'grades_summary' => 'data nilai',
        'count_students' => 'data santri',
        'count_gtk' => 'data GTK',
        'academic_overview' => 'ringkasan akademik',
        'tahfidz_summary' => 'ringkasan tahfidz',
        'ljk_summary' => 'ringkasan LJK',
        'search_students' => 'data santri',
        'student_detail' => 'detail santri',
        'search_gtk' => 'data GTK',
        'gtk_detail' => 'detail GTK',
        'count_my_students' => 'data santri kelas Anda',
        'list_my_classes' => 'data kelas Anda',
        'my_schedule_today' => 'jadwal KBM',
        'class_schedule' => 'jadwal kelas',
        'school_schedule_today' => 'jadwal sekolah',
        'web_search' => 'pencarian web',
        'read_url' => 'halaman web',
        'snapshot' => 'ringkasan data',
        'system prompt' => 'panduan internal',
    ];

    /**
     * @return array{blocks: array<int, array<string, mixed>>, text: string, sources: array<int, array<string, mixed>>, parsed: bool}
     */
    public function parse(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return $this->fallback('');
        }

        $json = $this->extractJson($raw);
        $decoded = $json !== null ? json_decode($json, true) : null;

        if (! is_array($decoded)) {
            return $this->fallback($raw);
        }

        $blocks = $decoded['blocks'] ?? $decoded;

        if (! is_array($blocks)) {
            return $this->fallback($raw);
        }

        // Jika model mengirim objek satu blok, bungkus menjadi list.
        if (isset($blocks['type'])) {
            $blocks = [$blocks];
        }

        $normalized = [];
        foreach (array_slice(array_values($blocks), 0, (int) config('ai.block_limits.max_blocks', 12)) as $block) {
            if (! is_array($block)) {
                continue;
            }

            $item = $this->normalizeBlock($block);
            if ($item !== null) {
                $normalized[] = $item;
            }
        }

        if ($normalized === []) {
            return $this->fallback($raw);
        }

        $text = collect($normalized)
            ->where('type', 'text')
            ->pluck('text')
            ->filter()
            ->implode("\n\n");

        if ($text === '') {
            $text = $this->blocksToPlainText($normalized);
        }

        return [
            'blocks' => $normalized,
            'text' => $text,
            'sources' => collect($normalized)->where('type', 'source')->values()->all(),
            'parsed' => true,
        ];
    }

    /**
     * @return array{blocks: array<int, array<string, mixed>>, text: string, sources: array<int, array<string, mixed>>, parsed: bool}
     */
    private function fallback(string $raw): array
    {
        $text = trim(preg_replace('/^```(?:json|markdown|md)?\s*|\s*```$/i', '', $raw) ?? $raw);

        // Jika JSON tidak valid tetapi masih memuat blok text, ambil teksnya
        // agar pengguna tidak melihat potongan kode/JSON mentah.
        if (preg_match_all('/"text"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $text, $matches)) {
            $extracted = array_map(function ($value) {
                $decoded = json_decode('"'.$value.'"', true);

                return is_string($decoded) ? $decoded : $value;
            }, $matches[1] ?? []);

            $joined = trim(implode("\n\n", array_filter($extracted)));
            if ($joined !== '') {
                $text = $joined;
            }
        }

        $text = $this->cleanText($text);

        if ($text === '' || str_starts_with(ltrim($text), '{') || str_starts_with(ltrim($text), '[')) {
            $text = 'Maaf, jawaban AI belum dapat ditampilkan. Silakan coba lagi.';
        }

        return [
            'blocks' => [['type' => 'text', 'text' => $text]],
            'text' => $text,
            'sources' => [],
            'parsed' => false,
        ];
    }

    private function extractJson(string $raw): ?string
    {
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw)) ?? $raw;
        $raw = preg_replace('/\s*```$/i', '', $raw) ?? $raw;

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $raw;
        }

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $candidate = substr($raw, $start, $end - $start + 1);

        return is_array(json_decode($candidate, true)) ? $candidate : null;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function normalizeBlock(array $block): ?array
    {
        $type = strtolower(trim((string) ($block['type'] ?? '')));

        return match ($type) {
            'text', 'paragraph', 'answer' => $this->textBlock($block),
            'metric', 'stat', 'kpi' => $this->metricBlock($block),
            'table', 'tabel' => $this->tableBlock($block),
            'chart', 'grafik' => $this->chartBlock($block),
            'source', 'sumber' => $this->sourceBlock($block),
            default => isset($block['text']) ? $this->textBlock($block) : null,
        };
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function textBlock(array $block): ?array
    {
        $text = $this->cleanText((string) ($block['text'] ?? $block['content'] ?? ''));

        if ($text === '') {
            return null;
        }

        return [
            'type' => 'text',
            'text' => Str::limit($text, (int) config('ai.block_limits.max_text_chars', 6000), ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function metricBlock(array $block): ?array
    {
        $label = $this->cleanText((string) ($block['label'] ?? $block['title'] ?? ''));
        $value = $block['value'] ?? null;

        if ($label === '' || (! is_scalar($value) && $value !== null)) {
            return null;
        }

        return [
            'type' => 'metric',
            'label' => Str::limit($label, 80, ''),
            'value' => $this->scalar($value),
            'unit' => $this->cleanText((string) ($block['unit'] ?? '')),
            'delta' => $this->cleanText((string) ($block['delta'] ?? '')),
            'color' => $this->color((string) ($block['color'] ?? 'primary')),
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function tableBlock(array $block): ?array
    {
        $columns = collect($block['columns'] ?? [])
            ->filter(fn ($column) => is_scalar($column))
            ->map(fn ($column) => Str::limit($this->cleanText((string) $column), 60, ''))
            ->values()
            ->take((int) config('ai.block_limits.max_table_columns', 10))
            ->all();

        $rows = collect($block['rows'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->take((int) config('ai.block_limits.max_table_rows', 40))
            ->map(function (array $row) use ($columns) {
                $values = array_values($row);

                return collect(array_slice($values, 0, count($columns) ?: 10))
                    ->map(fn ($value) => $this->scalar($value))
                    ->values()
                    ->all();
            })
            ->all();

        if ($columns === [] || $rows === []) {
            return null;
        }

        return [
            'type' => 'table',
            'title' => Str::limit($this->cleanText((string) ($block['title'] ?? '')), 100, ''),
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function chartBlock(array $block): ?array
    {
        $chartType = strtolower((string) ($block['chart_type'] ?? $block['chartType'] ?? $block['type_chart'] ?? 'bar'));
        $chartType = match ($chartType) {
            'column' => 'bar',
            'doughnut' => 'donut',
            default => $chartType,
        };
        if (! in_array($chartType, self::CHART_TYPES, true)) {
            $chartType = 'bar';
        }

        $labels = collect($block['labels'] ?? $block['categories'] ?? [])
            ->filter(fn ($label) => is_scalar($label))
            ->map(fn ($label) => Str::limit($this->cleanText((string) $label), 40, ''))
            ->values()
            ->take((int) config('ai.block_limits.max_chart_points', 24))
            ->all();

        $rawSeries = $block['series'] ?? $block['data'] ?? [];

        if (is_array($rawSeries) && $rawSeries !== [] && ! array_is_list($rawSeries)) {
            $rawSeries = collect($rawSeries)->map(fn ($data, $name) => [
                'name' => is_string($name) ? $name : 'Nilai',
                'data' => is_array($data) ? $data : [$data],
            ])->values()->all();
        } elseif (is_array($rawSeries) && $rawSeries !== [] && ! is_array($rawSeries[0])) {
            $rawSeries = [['name' => 'Nilai', 'data' => $rawSeries]];
        }

        $series = collect($rawSeries)
            ->filter(fn ($serie) => is_array($serie))
            ->take((int) config('ai.block_limits.max_chart_series', 6))
            ->map(function (array $serie) {
                $data = collect($serie['data'] ?? [])
                    ->map(fn ($value) => is_numeric($value) ? round((float) $value, 2) : 0)
                    ->values()
                    ->all();

                return [
                    'name' => Str::limit($this->cleanText((string) ($serie['name'] ?? 'Nilai')), 60, ''),
                    'data' => $data,
                ];
            })
            ->filter(fn ($serie) => $serie['data'] !== [])
            ->values()
            ->all();

        if ($labels === [] || $series === []) {
            return null;
        }

        return [
            'type' => 'chart',
            'title' => Str::limit($this->cleanText((string) ($block['title'] ?? '')), 100, ''),
            'chart_type' => $chartType,
            'labels' => $labels,
            'series' => $series,
            'unit' => $this->cleanText((string) ($block['unit'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function sourceBlock(array $block): ?array
    {
        $label = $this->cleanText((string) ($block['label'] ?? $block['name'] ?? ''));

        if ($label === '') {
            return null;
        }

        $url = filter_var((string) ($block['url'] ?? ''), FILTER_VALIDATE_URL);
        $scheme = $url ? strtolower((string) parse_url($url, PHP_URL_SCHEME)) : '';
        if ($url && ! in_array($scheme, ['http', 'https'], true)) {
            $url = false;
        }

        return [
            'type' => 'source',
            'label' => Str::limit($label, 100, ''),
            'detail' => Str::limit($this->cleanText((string) ($block['detail'] ?? '')), 200, ''),
            'tool' => Str::limit($this->cleanText((string) ($block['tool'] ?? '')), 80, ''),
            'url' => $url ?: null,
        ];
    }

    private function cleanText(string $value): string
    {
        $value = strip_tags($value);
        $value = str_ireplace(array_keys(self::INTERNAL_TERMS), array_values(self::INTERNAL_TERMS), $value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value);
    }

    private function scalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        return Str::limit($this->cleanText((string) $value), 300, '');
    }

    private function color(string $color): string
    {
        return in_array($color, ['primary', 'success', 'warning', 'danger', 'info', 'secondary'], true)
            ? $color
            : 'primary';
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function blocksToPlainText(array $blocks): string
    {
        $lines = [];

        foreach ($blocks as $block) {
            if ($block['type'] === 'metric') {
                $lines[] = $block['label'].': '.$block['value'].($block['unit'] ? ' '.$block['unit'] : '');
            }

            if ($block['type'] === 'table' && ! empty($block['title'])) {
                $lines[] = $block['title'];
            }
        }

        return implode("\n", $lines);
    }
}
