<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PenghargaanAkademik extends Model
{
    protected $table = 'admin_penghargaan_akademik';

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
        'jujur',
        'disiplin',
        'peduli',
        'adab',
        'kehadiran',
        'keaktifan',
        'nr_final',
        'ket',
    ];

    protected $casts = [
        'jujur' => 'integer',
        'disiplin' => 'integer',
        'peduli' => 'integer',
        'adab' => 'integer',
        'kehadiran' => 'integer',
        'keaktifan' => 'integer',
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
