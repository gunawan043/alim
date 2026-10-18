<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzGroupMember extends Model
{
    protected $table = 'tahfidz_group_members';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'tahfidz_group_id', 'student_id', 'academic_year_id',
        'join_date', 'leave_date', 'status', 'notes',
    ];

    protected $casts = [
        'join_date' => 'date',
        'leave_date' => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TahfidzGroup::class, 'tahfidz_group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
