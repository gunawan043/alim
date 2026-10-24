<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SubjectKktp extends Model
{
    protected $table = 'subject_kktp';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'subject_id',
        'school_id',
        'grade_level_id',
        'academic_year_id',
        'semester',
        'kktp_score',
        'kkm_score',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'kktp_score' => 'float',
        'kkm_score' => 'float',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = null;
            }
        });

        // Satu nilai KKTP disimpan di dua kolom: kkm_score (otoritatif untuk
        // leger/rapor) dan kktp_score (mirror untuk Peta Kurikulum). Jaga
        // keduanya tetap sinkron saat data dibuat atau diubah dari mana pun.
        static::saving(function (self $model) {
            if ($model->isDirty('kkm_score')) {
                $model->kktp_score = $model->kkm_score;
            } elseif ($model->isDirty('kktp_score')) {
                $model->kkm_score = $model->kktp_score;
            }
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
