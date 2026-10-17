<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryResident extends Model
{
    protected $table = 'dormitory_residents';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'room_id',
        'dormitory_id',
        'academic_year_id',
        'bed_number',
        'is_active',
        'check_in_date',
        'check_out_date',
        'check_out_reason',
        'notes',
    ];

    protected $casts = [
        'bed_number' => 'integer',
        'is_active' => 'boolean',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
