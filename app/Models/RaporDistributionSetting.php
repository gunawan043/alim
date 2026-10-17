<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Pengaturan tanggal pembagian rapor per satuan pendidikan + TA + semester.
 * Dipakai dokumen rapor (cetak & arsip) sebagai tanggal penerbitan.
 */
class RaporDistributionSetting extends Model
{
    protected $table = 'rapor_distribution_settings';

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
        'distribution_date',
        'city',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'distribution_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function forContext(?string $schoolId, ?string $academicYearId, ?string $semester): ?self
    {
        if (! $schoolId || ! $academicYearId || ! $semester) {
            return null;
        }

        return static::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->first();
    }
}
