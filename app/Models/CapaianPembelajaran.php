<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Capaian Pembelajaran (CP) — target kompetensi per mata pelajaran & fase.
 * Menjadi dasar penyusunan Tujuan Pembelajaran (TP).
 */
class CapaianPembelajaran extends Model
{
    use SoftDeletes;

    protected $table = 'capaian_pembelajaran';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'school_id',
        'subject_id',
        'fase',
        'elemen',
        'deskripsi',
        'urutan',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tujuanPembelajaran(): HasMany
    {
        return $this->hasMany(TujuanPembelajaran::class, 'capaian_pembelajaran_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * CP sekolah + CP global (school_id null) yang berlaku lintas satuan.
     */
    public function scopeForSchool($query, ?string $schoolId)
    {
        return $query->where(function ($q) use ($schoolId) {
            $q->whereNull('school_id');
            if ($schoolId) {
                $q->orWhere('school_id', $schoolId);
            }
        });
    }

    public function scopeByFase($query, ?string $fase)
    {
        return $fase ? $query->where('fase', $fase) : $query;
    }
}
