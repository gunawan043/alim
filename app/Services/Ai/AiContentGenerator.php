<?php

namespace App\Services\Ai;

/**
 * Penyusun draft RPM & soal via AI.
 *
 * Output diminta berupa JSON agar mudah ditampilkan/disunting. Bila model
 * tidak mengembalikan JSON valid, konten mentah tetap disimpan (tidak hilang).
 */
class AiContentGenerator
{
    public function __construct(private readonly AiProviderService $ai) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{ok: bool, content: array|null, raw: string|null, provider: string|null, model: string|null, error: string|null}
     */
    public function generate(string $type, array $context): array
    {
        $provider = $this->ai->defaultProvider();

        $messages = $type === 'soal'
            ? $this->soalMessages($context)
            : $this->rpmMessages($context);

        $result = $this->ai->chat($messages, [
            'temperature' => 0.4,
            'max_tokens' => 3500,
            'timeout' => 90,
        ], $provider);

        if (! $result['ok']) {
            return [
                'ok' => false,
                'content' => null,
                'raw' => null,
                'provider' => $provider?->name,
                'model' => $provider?->model,
                'error' => $result['error'] ?? 'Gagal memanggil AI.',
            ];
        }

        $parsed = $this->parseJson($result['content']);

        if ($type === 'soal') {
            $parsed = $this->normalizeSoal($parsed);
        }

        return [
            'ok' => true,
            'content' => $parsed,
            'raw' => $result['content'],
            'provider' => $provider?->name,
            'model' => $provider?->model,
            'error' => null,
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function soalMessages(array $context): array
    {
        $system = 'Anda penyusun soal pesantren yang teliti. Hasilkan HANYA JSON valid tanpa markdown. '
            .'Struktur: {"items":[{"pertanyaan":"...","bentuk":"pg|essay","pilihan":{"A":"...","B":"...","C":"...","D":"..."},"jawaban":"A atau jawaban ideal","pembahasan":"...","level":"C1|C2|C3|C4|C5|C6","indikator":"..."}]}. '
            .'Untuk essay, pilihan boleh objek kosong dan jawaban berupa jawaban ideal. '
            .'Soal harus sesuai tingkat/fase, tidak ambigu, dan bahasa sesuai permintaan.';

        $user = 'Konteks penyusunan soal:'."\n"
            .'- Mata pelajaran: '.($context['subject'] ?? '-')."\n"
            .'- Tingkat/Fase: '.($context['grade'] ?? '-').' / '.($context['fase'] ?? '-')."\n"
            .'- Tahun ajaran/semester: '.($context['academic_year'] ?? '-').' / '.($context['semester'] ?? '-')."\n"
            .'- Materi/TP/CP: '.($context['topik'] ?: 'Sesuaikan dengan mapel & tingkat')."\n"
            .'- Jumlah soal: '.(int) ($context['jumlah_soal'] ?? 5)."\n"
            .'- Bentuk: '.($context['bentuk_soal'] ?? 'campuran')."\n"
            .'- Level kognitif: '.($context['level_kognitif'] ?: 'campuran C1–C6')."\n"
            .'- Bahasa: '.($context['bahasa'] === 'ar' ? 'Arab' : 'Indonesia')."\n"
            .'- Instruksi tambahan: '.($context['instruksi'] ?: '-')."\n"
            .'Balas hanya JSON.';

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function rpmMessages(array $context): array
    {
        $system = 'Anda penyusun RPM (Rencana Pembelajaran Mendalam) pesantren. Hasilkan HANYA JSON valid tanpa markdown. '
            .'Struktur: {"judul":"...","identitas":{"mata_pelajaran":"...","kelas":"...","alokasi_waktu":"...","semester":"..."},'
            .'"capaian":"...","tujuan":["..."],"alur":["..."],'
            .'"langkah":[{"pertemuan":1,"pembuka":"...","inti":"...","penutup":"..."}],'
            .'"asesmen":{"formatif":"...","sumatif":"..."},"media":["..."],"refleksi":"..."}. '
            .'Isi harus operasional, kontekstual pesantren, dan bahasa sesuai permintaan.';

        $user = 'Konteks penyusunan RPM:'."\n"
            .'- Mata pelajaran: '.($context['subject'] ?? '-')."\n"
            .'- Tingkat/Fase: '.($context['grade'] ?? '-').' / '.($context['fase'] ?? '-')."\n"
            .'- Tahun ajaran/semester: '.($context['academic_year'] ?? '-').' / '.($context['semester'] ?? '-')."\n"
            .'- Materi/TP/CP: '.($context['topik'] ?: 'Sesuaikan dengan mapel & tingkat')."\n"
            .'- Jumlah pertemuan: '.(int) ($context['jumlah_pertemuan'] ?? 2)."\n"
            .'- Bahasa: '.($context['bahasa'] === 'ar' ? 'Arab' : 'Indonesia')."\n"
            .'- Instruksi tambahan: '.($context['instruksi'] ?: '-')."\n"
            .'Balas hanya JSON.';

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function parseJson(string $content): ?array
    {
        $text = trim($content);

        // Buang code fence ```json ... ```
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: ambil blok JSON pertama yang seimbang.
        $start = null;
        foreach (['{', '['] as $opening) {
            $pos = strpos($text, $opening);
            if ($pos !== false && ($start === null || $pos < $start)) {
                $start = $pos;
            }
        }

        if ($start === null) {
            return null;
        }

        $candidate = substr($text, $start);
        $candidate = preg_replace('/[\]\}][^\]\}]*$/', '', $candidate) ?? $candidate;
        $decoded = json_decode($candidate, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>}|null
     */
    private function normalizeSoal(?array $parsed): ?array
    {
        if (! $parsed) {
            return null;
        }

        $items = $parsed['items'] ?? $parsed['soal'] ?? $parsed['pertanyaan'] ?? $parsed;

        if (! is_array($items)) {
            return null;
        }

        if (isset($items['pertanyaan'])) {
            $items = [$items];
        }

        $items = array_values(array_filter($items, fn ($item) => is_array($item) && filled($item['pertanyaan'] ?? null)));

        return $items === [] ? null : ['items' => $items];
    }
}
