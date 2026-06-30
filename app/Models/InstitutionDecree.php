<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Traits\LogsDeletion;

class InstitutionDecree extends Model
{
    use LogsDeletion;

    protected $table = 'institution_decrees';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'issued_date'    => 'date',
        'effective_date' => 'date',
        'end_date'       => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'decree_number',
        'decree_type',
        'title',
        'description',
        'academic_year_id',
        'school_id',
        'issued_date',
        'effective_date',
        'end_date',
        'signed_by',
        'signed_position',
        'document_path',
        'document_filename',
        'status',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'decree_id');
    }
}