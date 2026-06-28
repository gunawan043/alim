<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExamSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exam_schedules';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'paket_soal_id',
        'study_group_id',
        'subject_id',
        'teacher_id',
        'admin_book_id',
        'academic_year_id',
        'semester',
        'jenis_ujian',
        'tanggal_ujian',
        'waktu_mulai',
        'waktu_selesai',
        'ruangan',
        'status',
        'tipe_pelaksanaan',
        'token_ujian',
        'token_expires_at',
        'allow_late_submit',
        'grace_period_minutes',
        'shuffle_questions',
        'shuffle_options',
        'show_score_after',
        'allow_review',
        'max_attempt_per_student',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_ujian' => 'date',
        'waktu_mulai' => 'datetime:H:i',
        'waktu_selesai' => 'datetime:H:i',
        'token_expires_at' => 'datetime',
        'allow_late_submit' => 'boolean',
        'grace_period_minutes' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_score_after' => 'boolean',
        'allow_review' => 'boolean',
        'max_attempt_per_student' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
            if (empty($m->token_ujian) && $m->tipe_pelaksanaan === 'token') {
                $m->token_ujian = strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(GtkProfile::class, 'teacher_id');
    }

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'schedule_id');
    }

    public function scopeUpcoming($q)
    {
        return $q->where('tanggal_ujian', '>=', now()->toDateString())
            ->whereIn('status', ['scheduled', 'active']);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function isTokenValid(?string $token): bool
    {
        if ($this->tipe_pelaksanaan !== 'token') {
            return true;
        }
        if ($this->token_ujian !== $token) {
            return false;
        }
        if ($this->token_expires_at && $this->token_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isWithinWindow(): bool
    {
        $now = now();
        $start = $this->tanggal_ujian->copy()->setTimeFromTimeString($this->waktu_mulai->format('H:i:s'));
        $end = $this->tanggal_ujian->copy()->setTimeFromTimeString($this->waktu_selesai->format('H:i:s'));
        if ($this->allow_late_submit && $this->grace_period_minutes > 0) {
            $end = $end->addMinutes($this->grace_period_minutes);
        }

        return $now->between($start, $end);
    }
}
