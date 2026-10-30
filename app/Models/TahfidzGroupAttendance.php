<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzGroupAttendance extends Model
{
    protected $table = 'tahfidz_group_attendances';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'tahfidz_group_id', 'student_id', 'academic_year_id', 'attendance_date',
        'session_type', 'status', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TahfidzGroup::class, 'tahfidz_group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'hadir' => 'Hadir',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpa' => 'Alpa',
            'belum_setor' => 'Belum setor',
            'belum_siap' => 'Belum siap setoran',
            default => ucfirst((string) $this->status),
        };
    }
}
