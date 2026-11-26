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
        'sts_material', 'sts_surah_start', 'sts_ayah_start', 'sts_surah_end', 'sts_ayah_end',
        'sts_juz', 'sts_page_start', 'sts_line_start', 'sts_page_end', 'sts_line_end',
        'sas_material', 'sas_surah_start', 'sas_ayah_start', 'sas_surah_end', 'sas_ayah_end',
        'sas_juz', 'sas_page_start', 'sas_line_start', 'sas_page_end', 'sas_line_end',
    ];

    protected $casts = [
        'weight_ziyadah' => 'float',
        'weight_rabt' => 'float',
        'weight_sts' => 'float',
        'weight_sas' => 'float',
        'is_active' => 'boolean',
        'sts_ayah_start' => 'integer',
        'sts_ayah_end' => 'integer',
        'sts_juz' => 'integer',
        'sts_page_start' => 'integer',
        'sts_line_start' => 'integer',
        'sts_page_end' => 'integer',
        'sts_line_end' => 'integer',
        'sas_ayah_start' => 'integer',
        'sas_ayah_end' => 'integer',
        'sas_juz' => 'integer',
        'sas_page_start' => 'integer',
        'sas_line_start' => 'integer',
        'sas_page_end' => 'integer',
        'sas_line_end' => 'integer',
    ];

    /**
     * Materi ujian satu jenis (sts/sas) dalam bentuk siap tampil.
     *
     * @return array<string, mixed>|null
     */
    public function examMaterial(string $jenis): ?array
    {
        $jenis = in_array($jenis, ['sts', 'sas'], true) ? $jenis : 'sts';

        $surahStart = $this->{$jenis.'_surah_start'};
        if (blank($surahStart) && blank($this->{$jenis.'_material'})) {
            return null;
        }

        return [
            'label' => $this->{$jenis.'_material'},
            'surah_start' => $surahStart,
            'surah_end' => $this->{$jenis.'_surah_end'},
            'ayah_start' => $this->{$jenis.'_ayah_start'},
            'ayah_end' => $this->{$jenis.'_ayah_end'},
            'juz' => $this->{$jenis.'_juz'},
            'page_start' => $this->{$jenis.'_page_start'},
            'line_start' => $this->{$jenis.'_line_start'},
            'page_end' => $this->{$jenis.'_page_end'},
            'line_end' => $this->{$jenis.'_line_end'},
        ];
    }

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
