<?php

namespace App\Models;

use App\Services\TahfidzGradebookCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Bobot NR Final mapel tahfidz milik DEPARTEMEN.
 *
 * Satu baris untuk setiap (sekolah × tahun ajaran × semester). Bobot ini
 * menjadi sumber tunggal: buku halaqah menyalin bobotnya agar rapor, leger,
 * dan cetak memakai angka yang sama, dan guru tidak dapat mengubahnya.
 */
class TahfidzWeightSetting extends Model
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
        ...[
            'weight_npt',
            'weight_ziyadah',
            'weight_rabt',
            'weight_tikrar',
            'weight_sts',
            'weight_sas',
        ],
        'updated_by',
    ];

    protected $casts = [
        'weight_npt' => 'float',
        'weight_ziyadah' => 'float',
        'weight_rabt' => 'float',
        'weight_tikrar' => 'float',
        'weight_sts' => 'float',
        'weight_sas' => 'float',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return array{npt: float, ziyadah: float, rabt: float, tikrar: float, sts: float, sas: float} */
    public function weights(): array
    {
        $weights = [];
        foreach (TahfidzGradebookCalculator::KEYS as $key) {
            $weights[$key] = (float) $this->{'weight_'.$key};
        }

        return $weights;
    }
}
