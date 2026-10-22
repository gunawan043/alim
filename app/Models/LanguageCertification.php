<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageCertification extends Model
{
    protected $table = 'language_certifications';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'student_id', 'certification_name', 'language', 'level',
        'score', 'issued_date', 'certificate_number', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'issued_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLanguageLabelAttribute(): string
    {
        return $this->language === 'inggris' ? 'Bahasa Inggris' : 'Bahasa Arab';
    }
}
