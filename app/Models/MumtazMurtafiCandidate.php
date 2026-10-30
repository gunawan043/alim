<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MumtazMurtafiCandidate extends Model
{
    protected $table = 'mumtaz_murtafi_candidates';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_DISMISSED = 'dismissed';

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'school_id',
        'study_group_id',
        'academic_year_id',
        'semester',
        'average_score',
        'class_rank',
        'status',
        'student_achievement_id',
        'generated_by',
        'posted_by',
        'posted_at',
        'dismissed_at',
        'notes',
    ];

    protected $casts = [
        'average_score' => 'decimal:2',
        'class_rank' => 'integer',
        'posted_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Diposting',
            self::STATUS_POSTED => 'Sudah Diposting',
            self::STATUS_DISMISSED => 'Diabaikan',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_POSTED => 'success',
            default => 'secondary',
        };
    }

    public function getSemesterLabelAttribute(): string
    {
        return ucfirst((string) $this->semester);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(StudentAchievement::class, 'student_achievement_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForSchool($query, ?string $schoolId)
    {
        return $schoolId ? $query->where('school_id', $schoolId) : $query;
    }
}
