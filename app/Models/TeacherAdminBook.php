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
        'semester',
        'teaching_id',
        'kktp_id',
        'is_active',
        'locked_at',
        'locked_by',
        'published_at',
        'published_by',
        'guardian_visible_columns',
        'nr_final_weight_rs',
        'nr_final_weight_sts',
        'nr_final_weight_sas',
        'sumatif_columns',
        'formatif_columns',
    ];

    protected $casts = [
        'nr_final_weight_rs' => 'float',
        'nr_final_weight_sts' => 'float',
        'nr_final_weight_sas' => 'float',
        'sumatif_columns' => 'array',
        'formatif_columns' => 'array',
        'locked_at' => 'datetime',
        'published_at' => 'datetime',
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

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
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
