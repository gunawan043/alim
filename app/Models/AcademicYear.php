<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcademicYear extends Model
{
    protected $table = 'academic_years';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'semester',
        'is_active',
        'start_date',
        'end_date',
        'registration_start',
        'registration_end',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'start_date'          => 'date',
        'end_date'            => 'date',
        'registration_start' => 'date',
        'registration_end'   => 'date',
    ];

    protected $appends = ['semester_text'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getSemesterTextAttribute(): string
    {
        return match ($this->semester) {
            'ganjil' => 'Ganjil',
            'genap'  => 'Genap',
            default  => ucfirst($this->semester ?? ''),
        };
    }

    public function getYearRangeAttribute(): string
    {
        if (!$this->start_date || !$this->end_date) {
            return $this->name;
        }
        return $this->start_date->format('d M Y') . ' – ' . $this->end_date->format('d M Y');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeSemester($q, string $semester)
    {
        return $q->where('semester', $semester);
    }
}
