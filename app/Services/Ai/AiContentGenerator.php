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

        $messages = match ($type) {
            'soal' => $this->soalMessages($context),
            'projek' => $this->projectMessages($context),
            default => $this->rpmMessages($context),
        };

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
        $curriculum = is_array($context['curriculum'] ?? null) ? $context['curriculum'] : [];
        $isKbc = ($curriculum['code'] ?? '') === 'kbc_kemenag' || ($curriculum['authority'] ?? '') === 'kemenag';
        $values = array_values((array) ($curriculum['values'] ?? []));

        $system = 'Anda penyusun soal pesantren yang teliti. Hasilkan HANYA JSON valid tanpa markdown. '
            .'Struktur: {"items":[{"pertanyaan":"...","bentuk":"pg|essay","pilihan":{"A":"...","B":"...","C":"...","D":"..."},"jawaban":"A atau jawaban ideal","pembahasan":"...","level":"C1|C2|C3|C4|C5|C6","indikator":"..."}]}. '
            .'Untuk essay, pilihan boleh objek kosong dan jawaban berupa jawaban ideal. '
            .'Soal harus sesuai tingkat/fase, tidak ambigu, dan bahasa sesuai permintaan.';

        if ($isKbc) {
            $system .= ' Integrasikan Kurikulum Berbasis Cinta (KBC): setiap butir harus mendorong salah satu nilai cinta, '
                .'tanpa memaksakan nilai yang tidak relevan dengan materi. Indikator harus mengukur pengetahuan sekaligus sikap.';
        }

        $user = 'Konteks penyusunan soal:'."\n"
            .'- Mata pelajaran: '.($context['subject'] ?? '-')."\n"
            .'- Tingkat/Fase: '.($context['grade'] ?? '-').' / '.($context['fase'] ?? '-')."\n"
            .'- Tahun ajaran/semester: '.($context['academic_year'] ?? '-').' / '.($context['semester'] ?? '-')."\n"
            .'- Profil kurikulum: '.($curriculum['name'] ?? '-')."\n"
            .'- Nilai/dimensi: '.($values ? implode(', ', $values) : '-')."\n"
            .'- Materi/TP/CP: '.($context['topik'] ?: 'Sesuaikan dengan mapel & tingkat')."\n"
            .'- Jumlah soal: '.(int) ($context['jumlah_soal'] ?? 5)."\n"
            .'- Bentuk: '.($context['bentuk_soal'] ?? 'campuran')."\n"
            .'- Level kognitif: '.($context['level_kognitif'] ?: 'campuran C1–C6')."\n"
            .'- Bahasa: '.($context['bahasa'] === 'ar' ? 'Arab' : 'Indonesia')."\n"
            .'- Instruksi tambahan: '.($context['instruksi'] ?: '-')."\n";
        if ($isKbc) {
            $user .= '- Instruksi KBC: cantumkan nilai cinta yang disasar pada kolom indikator, misalnya "Cinta kepada sesama: ...".'."\n";
        }
        $user .= 'Balas hanya JSON.';

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
        $curriculum = is_array($context['curriculum'] ?? null) ? $context['curriculum'] : [];
        $isKbc = ($curriculum['code'] ?? '') === 'kbc_kemenag' || ($curriculum['authority'] ?? '') === 'kemenag';
        $values = array_values((array) ($curriculum['values'] ?? []));

        $system = 'Anda penyusun '.($isKbc ? 'Modul Ajar Kurikulum Berbasis Cinta (KBC)' : 'RPM (Rencana Pembelajaran Mendalam)').' pesantren. '
            .'Hasilkan HANYA JSON valid tanpa markdown. '
            .'Struktur: {"judul":"...","identitas":{"mata_pelajaran":"...","kelas":"...","alokasi_waktu":"...","semester":"..."},'
            .'"capaian":"...","tujuan":["..."],"alur":["..."],'
            .'"langkah":[{"pertemuan":1,"pembuka":"...","inti":"...","penutup":"..."}],'
            .'"asesmen":{"formatif":"...","sumatif":"..."},"media":["..."],"refleksi":"..."';

        if ($isKbc) {
            $system .= ',"kbc":{"tema":"...","materi_insersi":"...","pilar":["..."],"indikator":"...","refleksi":"...","asesmen":"..."}';
        } else {
            $system .= ',"p5":{"tema":"...","dimensi":["..."],"catatan":"..."}';
        }
        $system .= '}. Isi harus operasional, kontekstual, dan bahasa sesuai permintaan.';

        if ($isKbc) {
            $system .= ' KBC wajib mengintegrasikan nilai cinta secara bermakna, bukan sekadar menempelkan slogan. '
                .'Gunakan tema, materi insersi, pilar, indikator, refleksi, dan asesmen nilai cinta yang eksplisit.';
        } else {
            $system .= ' Gunakan prinsip pembelajaran mendalam: berkesadaran, bermakna, dan menggembirakan.';
        }

        $user = 'Konteks penyusunan '.($isKbc ? 'Modul Ajar KBC' : 'RPM').':'."\n"
            .'- Mata pelajaran: '.($context['subject'] ?? '-')."\n"
            .'- Tingkat/Fase: '.($context['grade'] ?? '-').' / '.($context['fase'] ?? '-')."\n"
            .'- Tahun ajaran/semester: '.($context['academic_year'] ?? '-').' / '.($context['semester'] ?? '-')."\n"
            .'- Profil kurikulum: '.($curriculum['name'] ?? '-')."\n"
            .'- Nilai/dimensi: '.($values ? implode(', ', $values) : '-')."\n"
            .'- Kokurikuler: '.($curriculum['kokurikuler'] ?? '-')."\n"
            .'- Materi/TP/CP: '.($context['topik'] ?: 'Sesuaikan dengan mapel & tingkat')."\n"
            .'- Jumlah pertemuan: '.(int) ($context['jumlah_pertemuan'] ?? 2)."\n"
            .'- Bahasa: '.($context['bahasa'] === 'ar' ? 'Arab' : 'Indonesia')."\n"
            .'- Instruksi tambahan: '.($context['instruksi'] ?: '-')."\n";
        if ($isKbc) {
            $user .= '- Pilih minimal satu pilar cinta yang paling relevan dan tuliskan indikator yang dapat diamati.'."\n";
        }
        $user .= 'Balas hanya JSON.';

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function projectMessages(array $context): array
    {
        $curriculum = is_array($context['curriculum'] ?? null) ? $context['curriculum'] : [];
        $isKbc = ($curriculum['code'] ?? '') === 'kbc_kemenag' || ($curriculum['authority'] ?? '') === 'kemenag';
        $values = array_values((array) ($curriculum['values'] ?? []));
        $projectType = $context['project_type'] ?? ($isKbc ? 'p5ra' : 'p5');

        $system = 'Anda perancang projek kokurikuler '.strtoupper((string) $projectType).' untuk satuan pendidikan. '
            .'Hasilkan HANYA JSON valid tanpa markdown. '
            .'Struktur: {"judul":"...","tema":"...","deskripsi":"...","dimensi":["..."],'
            .'"tujuan":["..."],"aktivitas":[{"judul":"...","deskripsi":"...","pekan":1}],'
            .'"asesmen":{"formatif":"...","sumatif":"...","rubrik":"..."}}. '
            .'Projek harus kontekstual, kolaboratif, aman, dan dapat dilaksanakan.';

        if ($isKbc) {
            $system .= ' Integrasikan Kurikulum Berbasis Cinta: pilih pilar cinta yang relevan, rumuskan aksi nyata, refleksi, dan asesmen sikap.';
        } else {
            $system .= ' Gunakan dimensi Profil Pelajar Pancasila dan prinsip pembelajaran mendalam.';
        }

        $user = 'Konteks penyusunan projek:'."\n"
            .'- Jenis projek: '.strtoupper((string) $projectType)."\n"
            .'- Profil kurikulum: '.($curriculum['name'] ?? '-')."\n"
            .'- Dimensi/nilai yang tersedia: '.($values ? implode(', ', $values) : '-')."\n"
            .'- Tema/topik: '.($context['topik'] ?: 'Tentukan tema yang relevan')."\n"
            .'- Jenjang/kelas: '.($context['grade'] ?? '-')."\n"
            .'- Tahun ajaran/semester: '.($context['academic_year'] ?? '-').' / '.($context['semester'] ?? '-')."\n"
            .'- Jumlah aktivitas: '.(int) ($context['jumlah_pertemuan'] ?? 4)."\n"
            .'- Bahasa: '.($context['bahasa'] === 'ar' ? 'Arab' : 'Indonesia')."\n"
            .'- Instruksi tambahan: '.($context['instruksi'] ?: '-')."\n"
            .'Gunakan hanya nilai/dimensi yang tersedia. Balas hanya JSON.';

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
