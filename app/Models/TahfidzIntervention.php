<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzIntervention extends Model
{
    use HasUuids;

    protected $table = 'tahfidz_interventions';

    protected $fillable = [
        'student_id', 'tahfidz_group_id', 'academic_year_id', 'type', 'title',
        'notes', 'due_date', 'status', 'assigned_to', 'created_by', 'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TahfidzGroup::class, 'tahfidz_group_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'panggilan_wali' => 'Panggilan Wali',
            'bimbingan_tambahan' => 'Bimbingan Tambahan',
            'target_ulang' => 'Target Ulang',
            'motivasi' => 'Motivasi',
            default => 'Lainnya',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Berjalan',
            'selesai' => 'Selesai',
            'batal' => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }
}
