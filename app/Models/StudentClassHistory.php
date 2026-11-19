<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentClassHistory extends Model
{
    protected $table = 'student_class_histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());

        // Satu santri hanya boleh punya satu riwayat kelas aktif per tahun ajaran.
        // Tahun ajaran dapat tersimpan sebagai dua baris semester, jadi cocokkan
        // berdasarkan nama tahun ajaran.
        static::saving(function (StudentClassHistory $model) {
            if (! $model->is_active || ! $model->student_id || ! $model->academic_year_id) {
                return;
            }

            $year = AcademicYear::find($model->academic_year_id);
            $yearIds = $year
                ? AcademicYear::where('name', $year->name)->pluck('id')->all()
                : [$model->academic_year_id];

            $query = static::query()
                ->where('student_id', $model->student_id)
                ->whereIn('academic_year_id', $yearIds)
                ->where('is_active', true);

            if ($model->exists) {
                $query->where('id', '!=', $model->id);
            }

            $query->update(['is_active' => false]);
        });
    }

    protected $fillable = [
        'student_id', 'study_group_id', 'academic_year_id',
        'attendance_number', 'is_active', 'notes', 'join_date', 'leave_date',
    ];

    protected $casts = [
        'attendance_number' => 'integer',
        'is_active' => 'boolean',
        'join_date' => 'date',
        'leave_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
