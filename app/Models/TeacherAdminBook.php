<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TeacherAdminBook extends Model
{
    protected $table = 'teacher_admin_books';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'teacher_id',
        'subject_id',
        'study_group_id',
        'school_id',
        'academic_year_id',
        'decree_id',
        'semester',
        'teaching_id',
        'kktp_id',
        'is_active',
        'locked_at',
        'locked_by',
        'published_at',
        'published_by',
        'auto_publish',
        'guardian_visible_columns',
        'nr_final_weight_rs',
        'nr_final_weight_sts',
        'nr_final_weight_sas',
        'sumatif_columns',
        'sts_columns',
        'sas_columns',
        'formatif_columns',
    ];

    protected $casts = [
        'nr_final_weight_rs' => 'float',
        'nr_final_weight_sts' => 'float',
        'nr_final_weight_sas' => 'float',
        'sumatif_columns' => 'array',
        'sts_columns' => 'array',
        'sas_columns' => 'array',
        'formatif_columns' => 'array',
        'locked_at' => 'datetime',
        'published_at' => 'datetime',
        'auto_publish' => 'boolean',
        'guardian_visible_columns' => 'array',
    ];

    /**
     * Nilai final buku terkunci — perubahan hanya setelah dibuka kurikulum.
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Kolom nilai yang boleh dilihat wali/API.
     * Null berarti semua kolom (kompatibel dengan data lama).
     *
     * @return array<int, string>|null
     */
    public function guardianVisibleColumns(): ?array
    {
        return is_array($this->guardian_visible_columns) ? $this->guardian_visible_columns : null;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    /**
     * SK pembagian tugas yang menerbitkan buku nilai ini.
     */
    public function decree(): BelongsTo
    {
        return $this->belongsTo(InstitutionDecree::class, 'decree_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Batas data "Menu Guru": buku milik guru ini ditambah buku pada konteks
     * mengajar yang sama (co-teacher), karena satu konteks mapel+rombel+TA
     * hanya punya satu buku.
     *
     * Dipakai bersama oleh Buku Nilai dan Jurnal agar aturannya tidak
     * bercabang antar halaman.
     */
    public function scopeForTeacherWorkspace($query, ?string $teacherId = null)
    {
        $teacherId ??= auth()->id();

        $contexts = TeachingAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->get(['subject_id', 'study_group_id', 'academic_year_id']);

        return $query->where(function ($qq) use ($contexts, $teacherId) {
            $qq->where('teacher_id', $teacherId);

            foreach ($contexts as $c) {
                $qq->orWhere(fn ($x) => $x
                    ->where('subject_id', $c->subject_id)
                    ->where('study_group_id', $c->study_group_id)
                    ->where('academic_year_id', $c->academic_year_id));
            }
        });
    }

    public function nilaiSumatifs(): HasMany
    {
        return $this->hasMany(NilaiSumatif::class, 'admin_book_id');
    }

    public function nilaiFormatif(): HasMany
    {
        return $this->hasMany(NilaiFormatif::class, 'admin_book_id');
    }

    public function penghargaanAkademik(): HasMany
    {
        return $this->hasMany(PenghargaanAkademik::class, 'admin_book_id');
    }

    public function pembiasaanPagi(): HasMany
    {
        return $this->hasMany(PembiasaanPagi::class, 'admin_book_id');
    }

    public function kktp(): BelongsTo
    {
        return $this->belongsTo(SubjectKktp::class, 'kktp_id');
    }
}
