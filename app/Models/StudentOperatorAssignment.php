<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentOperatorAssignment extends Model
{
    use HasUuids;

    protected $table = 'student_operator_assignments';

    protected $fillable = [
        'student_id', 'user_id', 'operator_type', 'library_unit_id', 'dormitory_id',
        'is_active', 'assigned_by', 'assigned_at', 'ended_at', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function assigner(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
