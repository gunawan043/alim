<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Registri Dokumen Arsip — dokumen final/snapshot per periode.
 *
 * Berkas disimpan pada disk `local` (privat); kolom file_hash (SHA-256)
 * dipakai untuk verifikasi integritas.
 */
class ArchiveDocument extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'semester',
        'jenis',
        'language',
        'type',
        'title',
        'file_path',
        'file_hash',
        'file_size',
        'record_type',
        'record_id',
        'student_id',
        'study_group_id',
        'generated_by',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function record(): MorphTo
    {
        return $this->morphTo('record', 'record_type', 'record_id');
    }

    public function getShortHashAttribute(): string
    {
        return substr((string) $this->file_hash, 0, 12);
    }
}
