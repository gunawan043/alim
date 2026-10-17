<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzEvaluation extends Model
{
    protected $table = 'tahfidz_evaluations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'evaluator_id', 'tahfidz_group_id', 'academic_year_id',
        'evaluation_date', 'evaluation_type',
        'juz_diuji', 'halaman_diuji',
        'nilai_tahfizh', 'nilai_tajwid', 'nilai_fashohah', 'nilai_keseluruhan',
        'predikat', 'rekomendasi', 'status', 'notes',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'juz_diuji' => 'array',
        'halaman_diuji' => 'decimal:1',
        'nilai_tahfizh' => 'decimal:2',
        'nilai_tajwid' => 'decimal:2',
        'nilai_fashohah' => 'decimal:2',
        'nilai_keseluruhan' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getEvaluationTypeLabelAttribute(): string
    {
        return match ($this->evaluation_type) {
            'bulanan' => 'Bulanan',
            'tengah_semester' => 'Tengah Semester',
            'akhir_semester' => 'Akhir Semester',
            'kenaikan_juz' => 'Kenaikan Juz',
            default => ucfirst($this->evaluation_type ?? ''),
        };
    }

    public function getPredikatLabelAttribute(): string
    {
        return match ($this->predikat) {
            'mumtaz' => 'Mumtaz',
            'jayyid_jiddan' => 'Jayyid Jiddan',
            'jayyid' => 'Jayyid',
            'maqbul' => 'Maqbul',
            'rasib' => 'Rasib',
            default => $this->predikat ?? '-',
        };
    }
}
