<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExamAttempt extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exam_attempts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'paket_soal_id',
        'student_id',
        'admin_book_id',
        'study_group_id',
        'academic_year_id',
        'semester',
        'jenis_ujian',
        'started_at',
        'submitted_at',
        'status',
        'skor_total',
        'skor_otomatis',
        'skor_manual',
        'penilai_manual_id',
        'scored_at',
        'is_final',
        'flagged_suspicious',
        'notes',
        'schedule_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'scored_at' => 'datetime',
        'skor_total' => 'decimal:2',
        'skor_otomatis' => 'decimal:2',
        'skor_manual' => 'decimal:2',
        'duration_seconds' => 'integer',
        'is_final' => 'boolean',
        'flagged_suspicious' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
        });
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function manualGrader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penilai_manual_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'schedule_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function scopeInProgress($q)
    {
        return $q->where('status', 'in_progress');
    }

    public function scopeGraded($q)
    {
        return $q->where('status', 'graded');
    }

    public function scopeFinal($q)
    {
        return $q->where('is_final', true);
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['submitted', 'graded']);
    }

    public function getSkorAkhirAttribute(): ?float
    {
        if ($this->skor_total !== null) {
            return (float) $this->skor_total;
        }
        if ($this->skor_otomatis !== null && $this->skor_manual !== null) {
            return (float) ($this->skor_otomatis + $this->skor_manual);
        }

        return null;
    }
}
