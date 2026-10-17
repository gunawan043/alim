<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzStudentTarget extends Model
{
    protected $table = 'tahfidz_student_targets';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'tahfidz_group_id', 'academic_year_id', 'semester',
        'target_bulan', 'juz_start', 'juz_end',
        'surah_start_id', 'ayat_start', 'surah_end_id', 'ayat_end',
        'target_halaman', 'target_hadits', 'muqorrar_id',
        'assigned_by', 'notes',
    ];

    protected $casts = [
        'target_bulan' => 'integer',
        'juz_start' => 'integer',
        'juz_end' => 'integer',
        'ayat_start' => 'integer',
        'ayat_end' => 'integer',
        'target_halaman' => 'decimal:1',
        'target_hadits' => 'integer',
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
