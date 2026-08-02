<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentMahrom extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'name',
        'id_number',
        'relationship',
        'phone',
        'address',
        'photo_path',
        'is_active',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getRelationshipTextAttribute(): string
    {
        return match ($this->relationship) {
            'ayah' => 'Ayah',
            'ibu' => 'Ibu',
            'kakak' => 'Kakak',
            'adik' => 'Adik',
            'paman' => 'Paman',
            'bibi' => 'Bibi',
            'kakek' => 'Kakek',
            'nenek' => 'Nenek',
            'suami' => 'Suami',
            'istri' => 'Istri',
            'sepupu' => 'Sepupu',
            'wali' => 'Wali',
            'anak' => 'Anak',
            'lainnya' => 'Lainnya',
            default => ucfirst($this->relationship ?? ''),
        };
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path) {
            return asset('storage/'.$this->photo_path);
        }

        return null;
    }
}
