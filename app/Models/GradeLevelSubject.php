<?php

namespace App\Models;

use App\Models\StudyGroupSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GradeLevelSubject extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'academic_year_id',
        'grade_level_id',
        'subject_id',
        'allocation_hours',
        'track',
        'sort_order',
        'subject_source',
        'bobot',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allocation_hours' => 'integer',
        'sort_order' => 'integer',
        'bobot' => 'float',
    ];

    /**
     * Bobot rapor SAS untuk tingkat ini.
     *
     * Bobot adalah properti TINGKAT — satu nilai untuk semua rombel — sehingga
     * leger, buku nilai, dan rapor selalu membaca angka yang sama.
     */
    public function effectiveBobot(): float
    {
        if ($this->bobot !== null) {
            return (float) $this->bobot;
        }

        return StudyGroupSubject::bobotDariJp($this->allocation_hours);
    }

    /** Asal mapel. Rapor mencetak blok Pondok lebih dulu, lalu Dinas. */
    public const SOURCE_PONDOK = 'pondok';

    public const SOURCE_DINAS = 'dinas';

    /** Mapel yang diajarkan pondok sekaligus dinas. */
    public const SOURCE_PONDOK_DINAS = 'pondok_dinas';

    public const SOURCE_OPTIONS = [
        self::SOURCE_PONDOK => 'Pondok',
        self::SOURCE_DINAS => 'Dinas',
        self::SOURCE_PONDOK_DINAS => 'Pondok + Dinas',
    ];

    /**
     * Terjemahkan Kategori mapel menjadi asal mapel di Peta.
     *
     * Kategori dan asal di Peta sama-sama menyatakan Pondok vs Dinas, hanya
     * beda penamaan. Null = kategori belum dikenali, Peta dibiarkan kosong.
     */
    public static function sourceFromCategory(?string $category): ?string
    {
        return match ($category) {
            Subject::CATEGORY_PONDOK => self::SOURCE_PONDOK,
            Subject::CATEGORY_DINAS => self::SOURCE_DINAS,
            Subject::CATEGORY_DINAS_PONDOK => self::SOURCE_PONDOK_DINAS,
            default => null,
        };
    }

    /** Bobot urutan blok di rapor: pondok → pondok+dinas → dinas → belum ditandai. */
    public static function sourceRank(?string $source): int
    {
        return match ($source) {
            self::SOURCE_PONDOK => 0,
            self::SOURCE_PONDOK_DINAS => 1,
            self::SOURCE_DINAS => 2,
            default => 3,
        };
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * ARSIP PERIODE: baris versi tahun ajaran terpilih + baris lama (NULL)
     * sebagai fallback. Baris versi periode selalu diprioritaskan lewat
     * orderByAcademicYearFirst() agar tidak terjadi duplikasi tampilan.
     */
    public function scopeForAcademicYear(Builder $query, ?string $academicYearId): Builder
    {
        if (! $academicYearId) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId)
                ->orWhereNull('academic_year_id');
        });
    }

    /**
     * Urutkan baris versi periode lebih dulu; baris lama (NULL) menjadi fallback.
     */
    public function scopeOrderByAcademicYearFirst(Builder $query): Builder
    {
        return $query->orderByRaw('academic_year_id IS NULL ASC');
    }

    /**
     * Kunci unik logis struktur: jenjang + mapel + jalur (track).
     */
    public function structureKey(): string
    {
        return $this->grade_level_id.'|'.$this->subject_id.'|'.($this->track ?? '');
    }
}
