<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Jadwal piket kesiswaan — satu baris = satu petugas pada satu tanggal/shift.
 */
class KesiswaanDutySchedule extends Model
{
    protected $table = 'kesiswaan_duty_schedules';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'academic_year_id', 'user_id',
        'duty_date', 'shift', 'location', 'notes', 'created_by',
    ];

    protected $casts = [
        'duty_date' => 'date',
    ];

    public const SHIFTS = [
        'pagi' => 'Pagi',
        'siang' => 'Siang',
        'sore' => 'Sore',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function shiftLabel(): string
    {
        return self::SHIFTS[$this->shift] ?? '-';
    }
}
