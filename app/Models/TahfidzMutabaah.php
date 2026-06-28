<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzMutabaah extends Model
{
    protected $table = 'tahfidz_mutabaah';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'dormitory_id', 'tahfidz_group_id', 'academic_year_id',
        'recorded_by', 'record_date',
        'sholat_subuh', 'sholat_dzuhur', 'sholat_ashar', 'sholat_maghrib', 'sholat_isya',
        'sholat_tahajud', 'sholat_dhuha', 'puasa_sunnah', 'sedekah',
        'tilawah_halaman', 'tikror_mandiri_halaman', 'wirid_pagi', 'wirid_sore',
        'catatan_musyrif',
    ];

    protected $casts = [
        'record_date' => 'date',
        'sholat_tahajud' => 'integer',
        'sholat_dhuha' => 'integer',
        'puasa_sunnah' => 'integer',
        'sedekah' => 'integer',
        'tilawah_halaman' => 'integer',
        'tikror_mandiri_halaman' => 'integer',
        'wirid_pagi' => 'integer',
        'wirid_sore' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
