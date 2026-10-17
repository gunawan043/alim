<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Jadwal KBM (Kegiatan Belajar Mengajar) — single-slot model
 * yang MENGGANTIKAN pola ClassSchedule → ClassScheduleSlot → TimeSlot.
 *
 * Setiap baris = 1 pertemuan terjadwal (1 mapel × 1 rombel × 1 guru × 1 slot waktu).
 * Struktur flat ini memungkinkan conflict detection (guru/rombel bentrok)
 * dilakukan via SQL constraint, bukan via join berlapis.
 */
class JadwalKbm extends Model
{
    protected $table = 'jadwal_kbms';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'study_group_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'slot_index',
        'start_time',
        'end_time',
        'room',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'slot_index' => 'integer',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
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
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function getHariAttribute(): string
    {
        return match ($this->day_of_week) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => '?',
        };
    }
}
