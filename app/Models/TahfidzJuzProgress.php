<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzJuzProgress extends Model
{
    use HasUuids;

    protected $table = 'tahfidz_juz_progress';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'juz_number',
        'status',
        'halaman_completed',
        'total_halaman_juz',
        'percentage',
        'ziyadah_started_at',
        'ziyadah_completed_at',
        'last_setoran_date',
        'avg_nilai_setoran',
    ];

    protected $casts = [
        'juz_number' => 'integer',
        'halaman_completed' => 'decimal:1',
        'total_halaman_juz' => 'decimal:1',
        'percentage' => 'decimal:2',
        'avg_nilai_setoran' => 'decimal:2',
        'ziyadah_started_at' => 'date',
        'ziyadah_completed_at' => 'date',
        'last_setoran_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
