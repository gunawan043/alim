<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NilaiFormatif extends Model
{
    protected $table = 'admin_nilai_formatif';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'admin_book_id',
        'student_id',
        'academic_year_id',
        'semester',
        'skor_lkpd',
        'skor_diskusi',
        'skor_kuis',
        'skor_antarteman',
        'nr_final',
        'ket',
    ];

    protected $casts = [
        'skor_lkpd' => 'decimal:2',
        'skor_diskusi' => 'decimal:2',
        'skor_kuis' => 'decimal:2',
        'skor_antarteman' => 'decimal:2',
        'nr_final' => 'decimal:2',
    ];

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
