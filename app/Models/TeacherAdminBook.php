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
        'nr_final_weight_rs',
        'nr_final_weight_sts',
        'nr_final_weight_sas',
    ];

    protected $casts = [
        'nr_final_weight_rs' => 'float',
        'nr_final_weight_sts' => 'float',
        'nr_final_weight_sas' => 'float',
    ];

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

    public function nilaiSumatif(): HasMany
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
