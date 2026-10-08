<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
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
        'status',
        'desain',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'desain' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────

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
        return $this->tipe === self::TIPE_AGAMA ? self::GROUPS_AGAMA : self::GROUPS_UMUM;
    }

    /**
     * Desain default dengan seluruh bagian dikenal.
     *
     * @return array<string, string|null>
     */
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
