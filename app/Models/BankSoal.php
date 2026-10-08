<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BankSoal extends Model
{
    use HasFactory, LogsDeletion, SoftDeletes;

    protected $table = 'bank_soal';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'subject_id',
        'fase',
        'jenjang',
        'grade_level_id',
        'academic_year_id',
        'semester',
        'nama',
        'deskripsi',
        'jenis_soal',
        'tingkat_kesulitan_target',
        'is_public',
        'shared_scope',
        'is_central',
        'owner_user_id',
        'allow_cross_teacher_clone',
        'total_soal',
        'distribusi_kesulitan_aktual',
        'created_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_central' => 'boolean',
        'allow_cross_teacher_clone' => 'boolean',
        'distribusi_kesulitan_aktual' => 'array',
        'total_soal' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    public function soalApproved(): HasMany
    {
        return $this->hasMany(Soal::class)->where('status', 'approved');
    }

    public function tujuanPembelajaran(): BelongsToMany
    {
        return $this->belongsToMany(TujuanPembelajaran::class, 'bank_soal_tp', 'bank_soal_id', 'tp_id')
            ->withTimestamps();
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function scopeOwnedBy($query, string $userId)
    {
        return $query->where('owner_user_id', $userId);
    }

    public function scopeCentral($query)
    {
        return $query->where(function ($q) {
            $q->where('is_central', true)->orWhere('shared_scope', 'public_pool');
        });
    }

    /**
     * Konteks serumpun lintas satuan pendidikan:
     * mapel (+jenjang/kelas/TA/semester bila tersedia) — BUKAN school_id.
     */
    public function scopeRumpun($query, ?string $subjectId = null, array $context = [])
    {
        return $query
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($context['jenjang'] ?? null, fn ($q) => $q->where('jenjang', $context['jenjang']))
            ->when($context['grade_level_id'] ?? null, fn ($q) => $q->where('grade_level_id', $context['grade_level_id']))
            ->when($context['fase'] ?? null, fn ($q) => $q->where('fase', $context['fase']))
            ->when($context['academic_year_id'] ?? null, fn ($q) => $q->where('academic_year_id', $context['academic_year_id']))
            ->when($context['semester'] ?? null, fn ($q) => $q->where('semester', $context['semester']));
    }

    /**
     * Akses bank: milik sendiri, publik, internal sekolah, atau repositori
     * terpusat (central/public_pool) lintas satuan pendidikan.
     */
    public function scopeAccessibleBy($query, string $userId, ?string $schoolId = null)
    {
        return $query->where(function ($q) use ($userId, $schoolId) {
            $q->where('owner_user_id', $userId)
                ->orWhere('is_public', true)
                ->orWhere('is_central', true)
                ->orWhere('shared_scope', 'public_pool')
                ->orWhere(function ($q2) use ($schoolId) {
                    $q2->where('shared_scope', 'internal_school');

                    if ($schoolId) {
                        $q2->where('school_id', $schoolId);
                    }
                });
        });
    }
}
