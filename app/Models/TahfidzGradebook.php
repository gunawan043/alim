<?php

namespace App\Models;

use App\Services\TahfidzGradebookCalculator;
use App\Services\TahfidzGradebookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Buku nilai tahfidz untuk satu halaqah pada satu mapel dan satu periode.
 *
 * Berbeda dari `teacher_admin_books` yang diikat ke rombel: buku ini
 * mengikuti halaqah, karena anggota halaqah bisa berasal dari beberapa
 * rombel dalam satu tingkat.
 */
class TahfidzGradebook extends Model
{
    protected $table = 'tahfidz_gradebooks';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'tahfidz_group_id', 'subject_id', 'academic_year_id', 'school_id', 'semester',
        'weight_ziyadah', 'weight_rabt', 'weight_sts', 'weight_sas', 'is_active',
    ];

    protected $casts = [
        'weight_ziyadah' => 'float',
        'weight_rabt' => 'float',
        'weight_sts' => 'float',
        'weight_sas' => 'float',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TahfidzGroup::class, 'tahfidz_group_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(TahfidzGradebookScore::class, 'tahfidz_gradebook_id');
    }

    /** @return array{ziyadah: float, rabt: float, sts: float, sas: float} */
    public function weights(): array
    {
        $weights = [
            'ziyadah' => (float) $this->weight_ziyadah,
            'rabt' => (float) $this->weight_rabt,
            'sts' => (float) $this->weight_sts,
            'sas' => (float) $this->weight_sas,
        ];

        // Bobot 0 pada semua kolom bukan pilihan yang sah — itu tanda data
        // belum termuat. Jatuh ke bobot awal agar nilai tidak hilang diam-diam.
        if (array_sum($weights) <= 0) {
            return TahfidzGradebookService::DEFAULT_WEIGHTS;
        }

        return $weights;
    }

    public function totalWeight(): float
    {
        return array_sum($this->weights());
    }

    /**
     * Hitung ulang NR Final seluruh baris nilai pada buku ini.
     * Dipakai setelah bobot diubah agar angka lama tidak tertinggal.
     */
    public function recalcAll(): int
    {
        $calculator = app(TahfidzGradebookCalculator::class);
        $updated = 0;

        foreach ($this->scores()->get() as $score) {
            $calculator->apply($score, $this);
            if ($score->isDirty()) {
                $score->save();
            }
            $updated++;
        }

        return $updated;
    }
}
