<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryProgram extends Model
{
    use HasUuids;

    protected $table = 'library_programs';

    protected $fillable = [
        'library_unit_id', 'academic_year_id', 'type', 'title', 'category',
        'status', 'event_date', 'quantity', 'amount', 'description', 'data', 'created_by',
    ];

    protected $casts = [
        'event_date' => 'date', 'quantity' => 'integer',
        'amount' => 'decimal:2', 'data' => 'array',
    ];

    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
