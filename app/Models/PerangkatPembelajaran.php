<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Perangkat Pembelajaran / RPM (Rencana Pembelajaran Mendalam).
 *
 * Data terstruktur (JSON `desain`) dengan dua varian sesuai kebutuhan guru:
 *  - TIPE_AGAMA : Desain (TP, Model, Kemitraan, Lingkungan, Digital),
 *                 Pengalaman Belajar (Awal→Memahami→Mengaplikasi→Merefleksi→Penutup),
 *                 Asesmen Formatif & Sumatif.
 *  - TIPE_UMUM  : Identifikasi (Peserta Didik, Materi, Profil Lulusan),
 *                 Desain (CP, Topik, Lintas Disiplin, TP, Praktik Pedagogik,
 *                 Kemitraan, Lingkungan, Digital), Pengalaman Belajar, Asesmen.
 *
 * CP & TP tidak diinput ulang — dibaca dari ATP/TP yang sama.
 * Desain lama (fondasi Pembelajaran Mendalam) tetap dipertahankan.
 */
class PerangkatPembelajaran extends Model
{
    use HasCurriculumProfile;

    use SoftDeletes;

    protected $table = 'perangkat_pembelajaran';

    protected $keyType = 'string';

    public $incrementing = false;

    const STATUS_DRAFT = 'draft';

    const STATUS_FINAL = 'final';

    const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_FINAL => 'Final',
    ];

    // ── Tahap 2: alur dokumen (terpisah dari status kunci `final`) ─────
    const WORKFLOW_DRAFT = 'draft';

    const WORKFLOW_REVIEW = 'review';

    const WORKFLOW_REVISI = 'revisi';

    const WORKFLOW_APPROVED = 'approved';

    const WORKFLOW_OPTIONS = [
        self::WORKFLOW_DRAFT => 'Draft',
        self::WORKFLOW_REVIEW => 'Menunggu Review',
        self::WORKFLOW_REVISI => 'Perlu Perbaikan',
        self::WORKFLOW_APPROVED => 'Disetujui',
    ];

    const TIPE_UMUM = 'umum';

    const TIPE_AGAMA = 'agama';

    const TIPE_OPTIONS = [
        self::TIPE_AGAMA => 'Guru Agama (RPM Agama)',
        self::TIPE_UMUM => 'Guru Umum (RPM Umum)',
    ];

    /**
     * Fondasi Pembelajaran Mendalam (legacy, tetap didukung).
     */
    const DESAIN_SECTIONS = [
        'pertanyaan_pemantik' => 'Pertanyaan Pemantik',
        'pemahaman_bermakna' => 'Pemahaman Bermakna',
        'pengalaman_memahami' => 'Pengalaman Belajar — Memahami',
        'pengalaman_mengaplikasi' => 'Pengalaman Belajar — Mengaplikasi',
        'pengalaman_refleksi' => 'Pengalaman Belajar — Merefleksi',
        'konteks_nyata' => 'Koneksi Konteks Nyata',
        'asesmen_formatif' => 'Asesmen Formatif',
        'asesmen_sumatif' => 'Asesmen Sumatif',
        'diferensiasi' => 'Diferensiasi',
        'media_sumber' => 'Media & Sumber Belajar',
    ];

    /** Bagian yang hanya ada di struktur lama (tetap dipertahankan bila terisi). */
    const LEGACY_ONLY_SECTIONS = [
        'pertanyaan_pemantik' => 'Pertanyaan Pemantik',
        'pemahaman_bermakna' => 'Pemahaman Bermakna',
        'konteks_nyata' => 'Koneksi Konteks Nyata',
        'diferensiasi' => 'Diferensiasi',
        'media_sumber' => 'Media & Sumber Belajar',
    ];

    const SECTIONS_RPM = [
        'model_pembelajaran' => 'Model Pembelajaran',
        'kemitraan_pembelajaran' => 'Kemitraan Pembelajaran',
        'lingkungan_pembelajaran' => 'Lingkungan Pembelajaran',
        'pemanfaatan_digital' => 'Pemanfaatan Digital',
        'kegiatan_awal' => 'Kegiatan Awal',
        'pengalaman_memahami' => 'Memahami',
        'pengalaman_mengaplikasi' => 'Mengaplikasikan',
        'pengalaman_refleksi' => 'Merefleksikan',
        'penutup' => 'Penutup',
        'asesmen_formatif' => 'Asesmen Formatif',
        'asesmen_sumatif' => 'Asesmen Sumatif',
        'identifikasi_peserta_didik' => 'Peserta Didik',
        'identifikasi_materi' => 'Materi',
        'profil_lulusan' => 'Profil Lulusan',
        'topik' => 'Topik',
        'lintas_disiplin' => 'Lintas Disiplin Ilmu',
        'praktik_pedagogik' => 'Praktik Pedagogik',
        'tema_kbc' => 'Tema Kurikulum Berbasis Cinta',
        'materi_insersi' => 'Materi Insersi Nilai Cinta',
        'pilar_kbc' => 'Pilar Nilai Cinta',
        'indikator_kbc' => 'Indikator Nilai Cinta',
        'refleksi_kbc' => 'Refleksi Nilai Cinta',
        'asesmen_kbc' => 'Asesmen Nilai Cinta',
        'alokasi_waktu' => 'Alokasi Waktu',
        'asesmen_awal_jenis' => 'Jenis Asesmen — Awal',
        'asesmen_awal_teknik' => 'Teknik — Awal',
        'asesmen_awal_indikator' => 'Indikator — Awal',
        'asesmen_awal_instrumen' => 'Instrumen/Bukti — Awal',
        'asesmen_proses_jenis' => 'Jenis Asesmen — Proses',
        'asesmen_proses_teknik' => 'Teknik — Proses',
        'asesmen_proses_indikator' => 'Indikator — Proses',
        'asesmen_proses_instrumen' => 'Instrumen/Bukti — Proses',
        'asesmen_akhir_jenis' => 'Jenis Asesmen — Akhir',
        'asesmen_akhir_teknik' => 'Teknik — Akhir',
        'asesmen_akhir_indikator' => 'Indikator — Akhir',
        'asesmen_akhir_instrumen' => 'Instrumen/Bukti — Akhir',
    ];

    /**
     * Baris dan kolom tabel asesmen formatif/sumatif.
     */
    const ASSESSMENT_ROWS = [
        'awal' => 'Awal Pembelajaran',
        'proses' => 'Proses Pembelajaran',
        'akhir' => 'Akhir Pembelajaran',
    ];

    const ASSESSMENT_COLUMNS = [
        'jenis' => 'Jenis Asesmen',
        'teknik' => 'Teknik',
        'indikator' => 'Indikator',
        'instrumen' => 'Instrumen/Bukti',
    ];

    /**
     * Grup tampilan per tipe RPM (dipakai form & cetak PDF).
     */
    const GROUPS_AGAMA = [
        'desain' => [
            'label' => 'Desain Pembelajaran',
            'icon' => 'ri-focus-3-line',
            'desc' => 'Tujuan Pembelajaran diambil dari ATP; lengkapi model, kemitraan, lingkungan, dan pemanfaatan digital.',
            'keys' => ['model_pembelajaran', 'kemitraan_pembelajaran', 'lingkungan_pembelajaran', 'pemanfaatan_digital'],
        ],
        'pengalaman' => [
            'label' => 'Pengalaman Belajar',
            'icon' => 'ri-compasses-2-line',
            'desc' => 'Kegiatan awal → memahami → mengaplikasikan → merefleksikan → penutup.',
            'keys' => ['kegiatan_awal', 'pengalaman_memahami', 'pengalaman_mengaplikasi', 'pengalaman_refleksi', 'penutup'],
        ],
        'asesmen' => [
            'label' => 'Asesmen Formatif dan Sumatif',
            'icon' => 'ri-draft-line',
            'desc' => 'Rencana asesmen selama dan di akhir pembelajaran.',
            'keys' => ['asesmen_formatif', 'asesmen_sumatif'],
        ],
    ];

    const KBC_FIELDS = [
        'tema_kbc' => 'Tema Kurikulum Berbasis Cinta',
        'materi_insersi' => 'Materi Insersi Nilai Cinta',
        'pilar_kbc' => 'Pilar Nilai Cinta',
        'indikator_kbc' => 'Indikator Nilai Cinta',
        'refleksi_kbc' => 'Refleksi Nilai Cinta',
        'asesmen_kbc' => 'Asesmen Nilai Cinta',
    ];

    const KBC_CORE_FIELDS = [
        'tema_kbc',
        'materi_insersi',
        'pilar_kbc',
        'indikator_kbc',
    ];

    const GROUPS_KBC = [
        'kbc' => [
            'label' => 'Integrasi Kurikulum Berbasis Cinta',
            'icon' => 'ri-heart-3-line',
            'desc' => 'Tema, materi insersi, pilar, indikator, refleksi, dan asesmen nilai cinta.',
            'keys' => ['tema_kbc', 'materi_insersi', 'pilar_kbc', 'indikator_kbc', 'refleksi_kbc', 'asesmen_kbc'],
        ],
    ];

    const GROUPS_UMUM = [
        'identifikasi' => [
            'label' => 'Identifikasi',
            'icon' => 'ri-search-eye-line',
            'desc' => 'Peserta didik, materi, dan profil lulusan yang disasar.',
            'keys' => ['identifikasi_peserta_didik', 'identifikasi_materi', 'profil_lulusan'],
        ],
        'desain' => [
            'label' => 'Desain Pembelajaran',
            'icon' => 'ri-focus-3-line',
            'desc' => 'CP & TP diambil dari ATP; lengkapi topik, lintas disiplin, praktik pedagogik, kemitraan, lingkungan, dan digital.',
            'keys' => ['topik', 'lintas_disiplin', 'praktik_pedagogik', 'model_pembelajaran', 'kemitraan_pembelajaran', 'lingkungan_pembelajaran', 'pemanfaatan_digital'],
        ],
        'pengalaman' => [
            'label' => 'Pengalaman Belajar',
            'icon' => 'ri-compasses-2-line',
            'desc' => 'Kegiatan awal → memahami → mengaplikasikan → merefleksikan → penutup.',
            'keys' => ['kegiatan_awal', 'pengalaman_memahami', 'pengalaman_mengaplikasi', 'pengalaman_refleksi', 'penutup'],
        ],
        'asesmen' => [
            'label' => 'Asesmen Formatif dan Sumatif',
            'icon' => 'ri-draft-line',
            'desc' => 'Rencana asesmen selama dan di akhir pembelajaran.',
            'keys' => ['asesmen_formatif', 'asesmen_sumatif'],
        ],
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
            $m->root_id = $m->root_id ?: $m->id;
        });
    }

    protected $fillable = [
        'curriculum_profile_id',
        'id',
        'school_id',
        'academic_year_id',
        'semester',
        'subject_id',
        'grade_level_id',
        'study_group_id',
        'atp_id',
        'teacher_id',
        'judul',
        'tipe',
        'source',
        'ai_generated_content_id',
        'status',
        'workflow_status',
        'root_id',
        'parent_id',
        'version',
        'is_current',
        'submitted_at',
        'approved_at',
        'approved_by',
        'ksp_approved_at',
        'ksp_approved_by',
        'ksp_note',
        'locked_at',
        'locked_by',
        'desain',
        'layout_settings',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'desain' => 'array',
        'layout_settings' => 'array',
        'is_current' => 'boolean',
        'version' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'ksp_approved_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function aiGeneratedContent(): BelongsTo
    {
        return $this->belongsTo(AiGeneratedContent::class, 'ai_generated_content_id');
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'ai' ? 'AI — perlu ditinjau' : 'Manual';
    }

    public function curriculumProfile(): BelongsTo
    {
        return $this->belongsTo(CurriculumProfile::class, 'curriculum_profile_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function atp(): BelongsTo
    {
        return $this->belongsTo(AlurTujuanPembelajaran::class, 'atp_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Tahap 2: versi & alur persetujuan ────────────────────────────

    public function reviewAssignments(): MorphMany
    {
        return $this->morphMany(ReviewAssignment::class, 'reviewable');
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(self::class, 'root_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'root_id')->orderBy('version');
    }

    /**
     * Final terkunci: tidak dapat diubah/dihapus; revisi = versi baru.
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null || $this->status === self::STATUS_FINAL;
    }

    public function isEditable(): bool
    {
        return ! $this->isLocked();
    }

    public function isKspApproved(): bool
    {
        return $this->ksp_approved_at !== null;
    }

    public function isApproved(): bool
    {
        return $this->workflow_status === self::WORKFLOW_APPROVED;
    }

    /**
     * Buat versi baru dari dokumen (revisi) — dokumen lama tetap tersimpan.
     */
    public function createNewVersion(): self
    {
        /** @var self $new */
        $new = $this->replicate(['created_at', 'updated_at', 'deleted_at']);
        $new->id = (string) Str::uuid();
        $new->parent_id = $this->id;
        $new->root_id = $this->root_id ?: $this->id;
        $new->version = ($this->version ?: 1) + 1;
        $new->is_current = true;
        $new->status = self::STATUS_DRAFT;
        $new->workflow_status = self::WORKFLOW_DRAFT;
        $new->submitted_at = null;
        $new->approved_at = null;
        $new->approved_by = null;
        $new->ksp_approved_at = null;
        $new->ksp_approved_by = null;
        $new->ksp_note = null;
        $new->locked_at = null;
        $new->locked_by = null;
        $new->save();

        $this->forceFill(['is_current' => false])->saveQuietly();

        return $new->fresh();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Semua kunci desain yang dikenal sistem.
     *
     * @return array<int, string>
     */
    public static function allDesainKeys(): array
    {
        return array_values(array_unique(array_merge(
            array_keys(self::DESAIN_SECTIONS),
            array_keys(self::SECTIONS_RPM),
        )));
    }

    /**
     * Grup tampilan sesuai tipe RPM.
     */
    public function groups(): array
    {
        $format = $this->formatType();
        $groups = $format === \App\Services\Kurikulum\PerangkatFormatResolver::AGAMA
            ? self::GROUPS_AGAMA
            : self::GROUPS_UMUM;

        if ($this->curriculumProfile?->isKemenag()) {
            $groups = array_merge(self::GROUPS_KBC, $groups);
        }

        return $groups;
    }

    /**
     * Checklist kelengkapan integrasi KBC.
     *
     * @return array<string, array{label: string, filled: bool}>
     */
    public function kbcChecklist(?array $desain = null): array
    {
        $desain ??= $this->desain ?? [];

        return collect(self::KBC_FIELDS)
            ->mapWithKeys(fn ($label, $key) => [$key => [
                'label' => $label,
                'filled' => filled($desain[$key] ?? null),
            ]])
            ->all();
    }

    public function kbcCompletionPercentage(?array $desain = null): int
    {
        $checklist = $this->kbcChecklist($desain);
        if ($checklist === []) {
            return 0;
        }

        $filled = collect($checklist)->where('filled', true)->count();

        return (int) round(($filled / count($checklist)) * 100);
    }

    /**
     * @return array<int, string>
     */
    public function missingKbcFields(string $stage = 'final', ?array $desain = null): array
    {
        $keys = $stage === 'review' ? self::KBC_CORE_FIELDS : array_keys(self::KBC_FIELDS);
        $desain ??= $this->desain ?? [];

        return array_values(array_filter($keys, fn ($key) => blank($desain[$key] ?? null)));
    }

    /**
     * Validasi format minimal integrasi KBC.
     *
     * @return array<int, string>
     */
    public function kbcFormatIssues(?array $desain = null): array
    {
        $desain ??= $this->desain ?? [];
        $minimums = [
            'tema_kbc' => 3,
            'materi_insersi' => 15,
            'pilar_kbc' => 3,
            'indikator_kbc' => 15,
            'refleksi_kbc' => 10,
            'asesmen_kbc' => 10,
        ];

        $issues = [];
        foreach ($minimums as $key => $minimum) {
            $value = trim((string) ($desain[$key] ?? ''));
            if ($value !== '' && mb_strlen($value) < $minimum) {
                $issues[] = (self::KBC_FIELDS[$key] ?? $key).' minimal '.$minimum.' karakter.';
            }
        }

        $pilar = trim((string) ($desain['pilar_kbc'] ?? ''));
        $values = $this->curriculumProfile?->values() ?? [];
        if ($pilar !== '' && ! empty($values)) {
            $haystack = mb_strtolower($pilar);
            $matched = collect($values)->contains(function ($label, $key) use ($haystack) {
                return str_contains($haystack, mb_strtolower((string) $key))
                    || str_contains($haystack, mb_strtolower((string) $label));
            });

            if (! $matched) {
                $issues[] = 'Pilar KBC harus menyebut salah satu pilar/nilai pada profil.';
            }
        }

        return $issues;
    }

    /**
     * Desain default dengan seluruh bagian dikenal.
     *
     * @return array<string, string|null>
     */
    public static function defaultLayout(): array
    {
        return [
            'margin_top' => 12.0,
            'margin_right' => 12.0,
            'margin_bottom' => 14.0,
            'margin_left' => 15.0,
            'font_size' => 11.0,
            'title_font_size' => 13.0,
            'table_font_size' => 10.0,
            'line_height' => 1.25,
            'paper_size' => 'a4',
            'show_kop' => true,
            'use_digital_signature' => false,
            'show_stamp' => true,
        ];
    }

    public function layoutSettings(): array
    {
        $defaults = static::defaultLayout();
        $saved = is_array($this->layout_settings) ? $this->layout_settings : [];

        return [
            'margin_top' => (float) ($saved['margin_top'] ?? $defaults['margin_top']),
            'margin_right' => (float) ($saved['margin_right'] ?? $defaults['margin_right']),
            'margin_bottom' => (float) ($saved['margin_bottom'] ?? $defaults['margin_bottom']),
            'margin_left' => (float) ($saved['margin_left'] ?? $defaults['margin_left']),
            'font_size' => (float) ($saved['font_size'] ?? $defaults['font_size']),
            'title_font_size' => (float) ($saved['title_font_size'] ?? $defaults['title_font_size']),
            'table_font_size' => (float) ($saved['table_font_size'] ?? $defaults['table_font_size']),
            'line_height' => (float) ($saved['line_height'] ?? $defaults['line_height']),
            'paper_size' => in_array(($saved['paper_size'] ?? $defaults['paper_size']), ['a4', 'folio', 'letter'], true)
                ? ($saved['paper_size'] ?? $defaults['paper_size'])
                : 'a4',
            'show_kop' => (bool) ($saved['show_kop'] ?? $defaults['show_kop']),
            'use_digital_signature' => (bool) ($saved['use_digital_signature'] ?? $defaults['use_digital_signature']),
            'show_stamp' => (bool) ($saved['show_stamp'] ?? $defaults['show_stamp']),
        ];
    }

    public static function defaultDesain(): array
    {
        return array_fill_keys(self::allDesainKeys(), null);
    }

    /**
     * Gabungkan input desain dengan nilai lama — hanya kunci dikenal yang
     * diperbarui, kunci lain (termasuk bagian legacy yang terisi) tetap utuh.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    public function mergeDesain(array $input): array
    {
        $desain = is_array($this->desain) ? $this->desain : [];

        foreach ($input as $key => $value) {
            if (! in_array($key, self::allDesainKeys(), true)) {
                continue;
            }
            $desain[$key] = filled($value) ? (string) $value : null;
        }

        return $desain;
    }

    /**
     * Ambil nilai satu bagian desain.
     */
    public function desainValue(string $key): ?string
    {
        $desain = $this->desain ?? [];

        return filled($desain[$key] ?? null) ? (string) $desain[$key] : null;
    }

    /**
     * Format cetak berdasarkan jenis mapel (umum/agama/tahfidz).
     */
    public function formatType(): string
    {
        return app(\App\Services\Kurikulum\PerangkatFormatResolver::class)->forPerangkat($this);
    }

    /**
     * Alokasi waktu: nilai manual bila ada, selain itu total JP ATP.
     */
    public function alokasiWaktu(): string
    {
        $value = $this->desainValue('alokasi_waktu');
        if ($value !== null) {
            return $value;
        }

        $total = $this->atp?->total_jp;

        return $total ? (rtrim(rtrim(number_format((float) $total, 1, ',', '.'), '0'), ',').' JP') : '—';
    }

    /**
     * Matriks asesmen 3×4 dengan fallback dokumen lama.
     *
     * @return array<string, array<string, string>>
     */
    public function assessmentMatrix(): array
    {
        $matrix = [];
        $legacyProcess = (string) ($this->desainValue('asesmen_formatif') ?? '');
        $legacyFinal = (string) ($this->desainValue('asesmen_sumatif') ?? '');
        $fallback = [
            'proses' => $legacyProcess !== '' ? [
                'jenis' => 'Asesmen Formatif (data lama)',
                'instrumen' => $legacyProcess,
            ] : [],
            'akhir' => $legacyFinal !== '' ? [
                'jenis' => 'Asesmen Sumatif (data lama)',
                'instrumen' => $legacyFinal,
            ] : [],
        ];

        foreach (array_keys(self::ASSESSMENT_ROWS) as $row) {
            foreach (array_keys(self::ASSESSMENT_COLUMNS) as $column) {
                $value = (string) ($this->desainValue('asesmen_'.$row.'_'.$column) ?? '');
                if ($value === '' && isset($fallback[$row][$column])) {
                    $value = $fallback[$row][$column];
                }
                $matrix[$row][$column] = $value;
            }
        }

        return $matrix;
    }

    /**
     * Bagian legacy yang masih terisi (ditampilkan agar tidak hilang).
     *
     * @return array<string, string>
     */
    public function legacyFilledSections(): array
    {
        $filled = [];

        foreach (self::LEGACY_ONLY_SECTIONS as $key => $label) {
            if ($this->desainValue($key)) {
                $filled[$key] = $label;
            }
        }

        return $filled;
    }

    public function scopeBySchool($query, ?string $schoolId)
    {
        return $schoolId ? $query->where('school_id', $schoolId) : $query;
    }

    public function scopeByAcademicYear($query, ?string $academicYearId)
    {
        return $academicYearId ? $query->where('academic_year_id', $academicYearId) : $query;
    }

    public function scopeBySemester($query, ?string $semester)
    {
        return $semester ? $query->where('semester', $semester) : $query;
    }
}
