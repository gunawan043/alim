<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DormitoryReward extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'dormitory_id',
        'academic_year_id',
        'title',
        'category',
        'description',
        'level',
        'proof_path',
        'given_by',
        'awarded_date',
    ];

    protected $casts = [
        'awarded_date' => 'date',
    ];

    // ── Category constants ─────────────────────────────────────────

    public const CAT_PRESTASI = 'prestasi';

    public const CAT_TELADAN = 'teladan';

    public const CAT_KEBERSIHAN = 'kebersihan';

    public const CAT_KEDISIPLINAN = 'kedisiplinan';

    public const CAT_TAHFIDZ = 'tahfidz';

    public const CAT_AKHLAK = 'akhlak';

    public const LEVEL_BIASA = 'biasa';

    public const LEVEL_UGGALAN = 'unggulan';

    public const LEVEL_IMBITASA = 'istimewa';

    public static function categories(): array
    {
        return [
            self::CAT_PRESTASI => 'Prestasi',
            self::CAT_TELADAN => 'Teladan',
            self::CAT_KEBERSIHAN => 'Kebersihan',
            self::CAT_KEDISIPLINAN => 'Kedisiplinan',
            self::CAT_TAHFIDZ => 'Tahfidz',
            self::CAT_AKHLAK => 'Akhlak',
        ];
    }

    public static function levels(): array
    {
        return [
            self::LEVEL_BIASA => 'Biasa',
            self::LEVEL_UGGALAN => 'Unggulan',
            self::LEVEL_IMBITASA => 'Istimewa',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function givenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getCategoryTextAttribute(): string
    {
        $cats = self::categories();

        return $cats[$this->category] ?? ucfirst($this->category ?? '');
    }

    public function getLevelTextAttribute(): string
    {
        $lvl = self::levels();

        return $lvl[$this->level] ?? ucfirst($this->level ?? '');
    }
}
