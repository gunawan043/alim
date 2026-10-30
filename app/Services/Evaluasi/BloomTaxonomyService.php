<?php

namespace App\Services\Evaluasi;

/**
 * Klasifikasi level kognitif soal secara otomatis dengan Taksonomi Bloom revisi.
 *
 * Yang dideteksi hanya dimensi proses kognitif (C1–C6) dan rumpunnya
 * (L1/L2/L3). Tingkat kesukaran (mudah/sedang/sulit) sengaja TIDAK dihitung
 * di sini karena level kognitif dan tingkat kesukaran adalah dua dimensi
 * yang berbeda.
 */
class BloomTaxonomyService
{
    public const LEVELS = [
        'C1_mengingat' => [
            'code' => 'C1',
            'label' => 'C1 — Mengingat',
            'rumpun' => 'L1',
            'rumpun_label' => 'L1 — LOTS',
            'verbs' => [
                'mengingat', 'mengingatkan', 'menyebutkan', 'menyebut', 'sebutkan', 'sebut',
                'menuliskan', 'menulis', 'tuliskan', 'tulis', 'menunjukkan', 'mendefinisikan',
                'definisikan', 'mencocokkan', 'cocokkan', 'mengidentifikasi', 'identifikasi',
                'memilih', 'pilih', 'menyatakan', 'mengulang', 'mendaftar', 'daftar',
                'mengenali', 'mengetahui', 'membaca', 'menandai', 'menamai', 'memberi nama',
                'mencatat', 'apa yang dimaksud', 'pengertian', 'istilah',
            ],
        ],
        'C2_memahami' => [
            'code' => 'C2',
            'label' => 'C2 — Memahami',
            'rumpun' => 'L1',
            'rumpun_label' => 'L1 — LOTS',
            'verbs' => [
                'menjelaskan', 'jelaskan', 'menguraikan', 'uraikan', 'menafsirkan', 'tafsirkan',
                'merangkum', 'rangkum', 'meringkas', 'memparafrasekan', 'mengklasifikasikan',
                'klasifikasikan', 'mengelompokkan', 'kelompokkan', 'memberi contoh',
                'memberikan contoh', 'mendeskripsikan', 'deskripsikan', 'menerangkan',
                'membedakan', 'mengubah', 'memperkirakan', 'mengilustrasikan', 'menerjemahkan',
                'artikan', 'mengartikan', 'menceritakan', 'menjelaskan hubungan',
            ],
        ],
        'C3_menerapkan' => [
            'code' => 'C3',
            'label' => 'C3 — Menerapkan',
            'rumpun' => 'L2',
            'rumpun_label' => 'L2 — MOTS',
            'verbs' => [
                'menghitung', 'hitung', 'hitunglah', 'menerapkan', 'terapkan', 'menggunakan',
                'gunakan', 'mendemonstrasikan', 'demonstrasikan', 'menyelesaikan', 'selesaikan',
                'menentukan', 'tentukan', 'mengoperasikan', 'operasikan', 'mengukur', 'ukur',
                'melaksanakan', 'laksanakan', 'mempraktikkan', 'praktikkan', 'menghitung nilai',
                'menentukan nilai', 'menentukan hasil', 'menyusun langkah', 'menggunakan rumus',
            ],
        ],
        'C4_menganalisis' => [
            'code' => 'C4',
            'label' => 'C4 — Menganalisis',
            'rumpun' => 'L3',
            'rumpun_label' => 'L3 — HOTS',
            'verbs' => [
                'menganalisis', 'analisis', 'membandingkan', 'bandingkan', 'mengkorelasikan',
                'korelasikan', 'memeriksa', 'periksa', 'menguji', 'uji', 'memecahkan',
                'pecahkan', 'mendiagnosis', 'diagnosis', 'mengorganisasikan', 'organisasikan',
                'menemukan pola', 'menelaah', 'telaah', 'menghubungkan', 'hubungkan',
                'membedakan faktor', 'mengidentifikasi penyebab', 'menentukan hubungan',
                'menentukan sebab', 'menguraikan hubungan', 'menganalisis data',
            ],
        ],
        'C5_mengevaluasi' => [
            'code' => 'C5',
            'label' => 'C5 — Mengevaluasi',
            'rumpun' => 'L3',
            'rumpun_label' => 'L3 — HOTS',
            'verbs' => [
                'mengevaluasi', 'evaluasi', 'menilai', 'nilai', 'mengkritik', 'kritik',
                'menyimpulkan', 'simpulkan', 'memutuskan', 'putuskan', 'mempertimbangkan',
                'pertimbangkan', 'menimbang', 'timbang', 'memvalidasi', 'validasi',
                'menjustifikasi', 'justifikasi', 'mempertahankan', 'pertahankan',
                'merekomendasikan', 'rekomendasikan', 'memberi penilaian', 'menentukan kelayakan',
                'menilai kelayakan', 'menurut pendapatmu', 'setujukah kamu',
            ],
        ],
        'C6_mencipta' => [
            'code' => 'C6',
            'label' => 'C6 — Mencipta',
            'rumpun' => 'L3',
            'rumpun_label' => 'L3 — HOTS',
            'verbs' => [
                'merancang', 'rancang', 'rancanglah', 'menyusun', 'susun', 'susunlah',
                'membuat', 'buat', 'buatlah', 'menciptakan', 'ciptakan', 'ciptakanlah',
                'mengembangkan', 'kembangkan', 'kembangkanlah', 'mendesain', 'desain',
                'desainlah', 'memproduksi', 'produksi', 'mengarang', 'karang', 'merumuskan',
                'rumuskan', 'rumuskanlah', 'mengombinasikan', 'kombinasikan', 'mengkreasikan',
                'kreasikan', 'kreasikanlah', 'menciptakan gagasan', 'menyusun kembali',
                'membuat gagasan baru', 'mengembangkan solusi',
            ],
        ],
    ];

    public const RUMPUN = [
        'L1' => [
            'label' => 'L1 — LOTS',
            'description' => 'Mengingat dan memahami fakta, istilah, konsep dasar, atau informasi sederhana.',
        ],
        'L2' => [
            'label' => 'L2 — MOTS',
            'description' => 'Menerapkan konsep, rumus, atau prosedur pada situasi yang familiar.',
        ],
        'L3' => [
            'label' => 'L3 — HOTS',
            'description' => 'Menganalisis, mengevaluasi, atau mencipta solusi pada masalah yang lebih kompleks.',
        ],
    ];

    /**
     * @return array{
     *     level: string,
     *     level_code: string,
     *     level_label: string,
     *     rumpun: string,
     *     rumpun_label: string,
     *     confidence: int,
     *     matched_verbs: array<int, string>,
     *     source: string,
     *     explanation: string
     * }
     */
    public function classify(?string $question, ?string $indicator = null, ?string $material = null): array
    {
        $sources = [
            'indikator' => $this->normalize($indicator),
            'pertanyaan' => $this->normalize($question),
            'materi' => $this->normalize($material),
        ];

        $weights = ['indikator' => 3, 'pertanyaan' => 2, 'materi' => 1];
        $scores = array_fill_keys(array_keys(self::LEVELS), 0);
        $matches = [];
        $sourceByLevel = [];

        foreach ($sources as $source => $text) {
            if ($text === '') {
                continue;
            }

            foreach (self::LEVELS as $level => $definition) {
                $found = $this->findVerbs($text, $definition['verbs']);

                if ($found === []) {
                    continue;
                }

                $scores[$level] += $weights[$source] * count($found);
                $matches[$level] = array_values(array_unique(array_merge($matches[$level] ?? [], $found)));

                if (! isset($sourceByLevel[$level]) || $weights[$source] > $weights[$sourceByLevel[$level]]) {
                    $sourceByLevel[$level] = $source;
                }
            }
        }

        $level = $this->pickLevel($scores);

        // Bila tidak ada KKO yang dikenali, gunakan ciri bentuk pertanyaan.
        if ($level === null) {
            [$level, $confidence, $explanation] = $this->fallbackFromQuestion($sources['pertanyaan']);
            $definition = self::LEVELS[$level];

            return [
                'level' => $level,
                'level_code' => $definition['code'],
                'level_label' => $definition['label'],
                'rumpun' => $definition['rumpun'],
                'rumpun_label' => $definition['rumpun_label'],
                'confidence' => $confidence,
                'matched_verbs' => [],
                'source' => 'heuristik',
                'explanation' => $explanation,
            ];
        }

        $definition = self::LEVELS[$level];
        $topScore = $scores[$level];
        $secondScore = collect($scores)->except([$level])->max() ?: 0;
        $confidence = min(95, 60 + ($topScore * 6) + max(0, $topScore - $secondScore) * 3);

        if (($sourceByLevel[$level] ?? null) === 'indikator') {
            $confidence = min(97, $confidence + 5);
        }

        return [
            'level' => $level,
            'level_code' => $definition['code'],
            'level_label' => $definition['label'],
            'rumpun' => $definition['rumpun'],
            'rumpun_label' => $definition['rumpun_label'],
            'confidence' => (int) $confidence,
            'matched_verbs' => $matches[$level] ?? [],
            'source' => $sourceByLevel[$level] ?? 'heuristik',
            'explanation' => $this->explanation($definition, $sourceByLevel[$level] ?? 'heuristik', $matches[$level] ?? []),
        ];
    }

    /**
     * Label ramah untuk level enum, termasuk kompatibilitas nilai lama.
     */
    public static function levelLabel(?string $level): string
    {
        if (! $level) {
            return 'Belum terdeteksi';
        }

        $level = $level === 'C6_mencita' ? 'C6_mencipta' : $level;

        return self::LEVELS[$level]['label'] ?? $level;
    }

    public static function rumpunLabel(?string $rumpun): string
    {
        if (! $rumpun) {
            return '—';
        }

        return self::RUMPUN[$rumpun]['label'] ?? $rumpun;
    }

    public static function rumpunForLevel(?string $level): ?string
    {
        if (! $level) {
            return null;
        }

        $level = $level === 'C6_mencita' ? 'C6_mencipta' : $level;

        return self::LEVELS[$level]['rumpun'] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function levelOptions(): array
    {
        return collect(self::LEVELS)->mapWithKeys(fn ($definition, $key) => [$key => $definition['label']])->all();
    }

    private function normalize(?string $text): string
    {
        $text = strip_tags((string) $text);

        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($text)) ?? '', 'UTF-8');
    }

    /**
     * @param  array<int, string>  $verbs
     * @return array<int, string>
     */
    private function findVerbs(string $text, array $verbs): array
    {
        $found = [];

        foreach ($verbs as $verb) {
            $pattern = '/(?<![a-z])'.preg_quote($verb, '/').'(?![a-z])/iu';

            if (preg_match($pattern, $text) === 1) {
                $found[] = $verb;
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * @param  array<string, int>  $scores
     */
    private function pickLevel(array $scores): ?string
    {
        $ranked = collect($scores)
            ->filter(fn ($score) => $score > 0)
            ->sort(function ($a, $b) {
                if ($a === $b) {
                    return 0;
                }

                return $a > $b ? -1 : 1;
            });

        if ($ranked->isEmpty()) {
            return null;
        }

        $top = $ranked->max();
        $candidates = $ranked->filter(fn ($score) => $score === $top)->keys();

        // Bila skor sama, pilih level kognitif yang lebih tinggi agar soal
        // dengan tuntutan berpikir kompleks tidak turun ke LOTS.
        $order = array_keys(self::LEVELS);

        return $candidates->sortByDesc(fn ($level) => array_search($level, $order, true))->first();
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     */
    private function fallbackFromQuestion(string $question): array
    {
        $patterns = [
            'C6_mencipta' => ['rancang', 'susun', 'buat', 'kembangkan', 'desain', 'ciptakan', 'kreasikan', 'rumuskan'],
            'C5_mengevaluasi' => ['evaluasi', 'nilai', 'kritik', 'simpulkan', 'setuju', 'pendapat', 'rekomendasi', 'kelayakan'],
            'C4_menganalisis' => ['analisis', 'banding', 'hubungan', 'pengaruh', 'sebab', 'akibat', 'pola', 'bedakan', 'periksa'],
            'C3_menerapkan' => ['hitung', 'tentukan', 'gunakan', 'terapkan', 'selesaikan', 'ukur', 'berapa hasil', 'hitunglah'],
            'C2_memahami' => ['jelaskan', 'uraikan', 'mengapa', 'bagaimana', 'mengartikan', 'contoh', 'deskripsi'],
            'C1_mengingat' => ['sebutkan', 'tuliskan', 'definisi', 'istilah', 'siapa', 'kapan', 'di mana', 'apa itu', 'apa yang dimaksud', 'pengertian'],
        ];

        foreach ($patterns as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($question, $keyword)) {
                    $definition = self::LEVELS[$level];

                    return [
                        $level,
                        55,
                        'Tidak ada kata kerja operasional yang eksplisit; bentuk pertanyaan mengarah ke '.$definition['label'].'.',
                    ];
                }
            }
        }

        return [
            'C2_memahami',
            40,
            'Tidak ada KKO yang dikenali. Level awal ditetapkan C2; guru dapat memvalidasi saat telaah soal.',
        ];
    }

    /**
     * @param  array<int, string>  $verbs
     */
    private function explanation(array $definition, string $source, array $verbs): string
    {
        $sourceLabel = match ($source) {
            'indikator' => 'indikator/tujuan pembelajaran',
            'pertanyaan' => 'pertanyaan',
            'materi' => 'materi',
            default => 'pola pertanyaan',
        };

        $verbText = $verbs === [] ? '' : ' Kata kerja terdeteksi: '.implode(', ', array_slice($verbs, 0, 4)).'.';

        return 'KKO pada '.$sourceLabel.' mengarah ke '.$definition['label'].'.'.$verbText;
    }
}
