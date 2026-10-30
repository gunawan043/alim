<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Satu baris mata pelajaran pada Surat Keterangan Lulus.
 *
 * subject_id null berarti baris manual (mis. "Konsentrasi Keahlian") yang
 * nilainya diisi manual atau dikosongkan.
 */
class GraduationCertificateSubject extends Model
{
    protected $table = 'graduation_certificate_subjects';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'subject_id', 'group_key', 'display_name', 'sort_order', 'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }
}
