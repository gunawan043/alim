<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryResident extends Model
{
    protected $table = 'dormitory_residents';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected static function booted(): void
    {
        // Setiap penempatan/checkout harus tercermin di tabel status boarding,
        // supaya dashboard & laporan tidak lagi menghitung "di asrama" dari
        // tabel yang hanya berisi santri yang sedang keluar.
        static::saved(function (self $resident) {
            $resident->syncBoardingStatus();
        });
    }

    /**
     * Jaga `student_boarding_statuses` tetap sejalan dengan data penghuni.
     * Status keluar (izin/UKS/kegiatan) tidak pernah ditimpa di sini.
     */
    public function syncBoardingStatus(): void
    {
        if (! $this->student_id) {
            return;
        }

        $status = StudentBoardingStatus::firstOrNew(['student_id' => $this->student_id]);

        if ($this->is_active) {
            if (! $status->exists) {
                $status->status = StudentBoardingStatus::IN_DORM;
                $status->effective_from = $this->check_in_date ?? now();
            }

            if ($status->status === StudentBoardingStatus::IN_DORM) {
                $status->dormitory_id = $this->dormitory_id;
                $status->room_id = $this->room_id;
            } else {
                $status->dormitory_id ??= $this->dormitory_id;
                $status->room_id ??= $this->room_id;
            }
        } elseif ($status->exists && $status->status === StudentBoardingStatus::IN_DORM) {
            $status->status = StudentBoardingStatus::CHECKED_OUT;
        } else {
            return;
        }

        $status->last_event_at = now();
        $status->save();
    }

    protected $fillable = [
        'student_id',
        'room_id',
        'dormitory_id',
        'academic_year_id',
        'bed_number',
        'is_active',
        'check_in_date',
        'check_out_date',
        'check_out_reason',
        'notes',
    ];

    protected $casts = [
        'bed_number' => 'integer',
        'is_active' => 'boolean',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
