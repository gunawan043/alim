<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzProgressRecap extends Model
{
    protected $table = 'tahfidz_progress_recaps';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'tahfidz_group_id', 'academic_year_id', 'semester',
        'total_juz_ziyadah', 'total_halaman_ziyadah',
        'total_juz_murajaah', 'total_halaman_murajaah',
        'total_setoran', 'total_hari_setoran',
        'rata_rata_nilai', 'pencapaian_target_persen',
        'last_position_juz', 'last_position_surah_id', 'last_position_ayat',
        'last_position_halaman', 'total_juz_completed', 'hadits_count',
    ];

    protected $casts = [
        'total_juz_ziyadah' => 'decimal:1',
        'total_halaman_ziyadah' => 'decimal:1',
        'total_juz_murajaah' => 'decimal:1',
        'total_halaman_murajaah' => 'decimal:1',
        'total_setoran' => 'integer',
        'total_hari_setoran' => 'integer',
        'rata_rata_nilai' => 'decimal:2',
        'pencapaian_target_persen' => 'decimal:2',
        'last_position_juz' => 'integer',
        'last_position_ayat' => 'integer',
        'last_position_halaman' => 'decimal:1',
        'total_juz_completed' => 'integer',
        'hadits_count' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
