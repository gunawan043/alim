<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PekanEfektif extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $table = 'pekan_efektif';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'semester',
        'minggu_ke',
        'tanggal_mulai',
        'tanggal_selesai',
        'jenis',
        'keterangan',
    ];

    protected $casts = [
        'semester' => 'integer',
        'minggu_ke' => 'integer',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    const JENIS_EFEKTIF = 'efektif';

    const JENIS_LIBUR = 'libur';

    const JENIS_UJIAN = 'ujian';

    const JENIS_KEGIATAN_SEKOLAH = 'kegiatan_sekolah';

    const JENIS_LAINNYA = 'lainnya';

    const JENIS_OPTIONS = [
        self::JENIS_EFEKTIF => 'Minggu Efektif',
        self::JENIS_LIBUR => 'Libur',
        self::JENIS_UJIAN => 'Ujian',
        self::JENIS_KEGIATAN_SEKOLAH => 'Kegiatan Sekolah',
        self::JENIS_LAINNYA => 'Lainnya',
    ];

    const SEMESTER_GANJIL = 1;

    const SEMESTER_GENAP = 2;

    const SEMESTER_OPTIONS = [
        self::SEMESTER_GANJIL => 'Semester 1 (Ganjil)',
        self::SEMESTER_GENAP => 'Semester 2 (Genap)',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function getDurasiHariAttribute(): int
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return 0;
        }

        return (int) Carbon::parse($this->tanggal_mulai)
            ->diffInDays(Carbon::parse($this->tanggal_selesai)) + 1;
    }

    public function scopeBySchool($query, ?string $schoolId)
    {
        if ($schoolId) {
            return $query->where('school_id', $schoolId);
        }

        return $query;
    }

    public function scopeByAcademicYear($query, ?string $academicYearId)
    {
        if ($academicYearId) {
            return $query->where('academic_year_id', $academicYearId);
        }

        return $query;
    }

    public function scopeBySemester($query, ?int $semester)
    {
        if ($semester) {
            return $query->where('semester', $semester);
        }

        return $query;
    }

    public function scopeByJenis($query, ?string $jenis)
    {
        if ($jenis) {
            return $query->where('jenis', $jenis);
        }

        return $query;
    }
}
