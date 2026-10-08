<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Kaldik extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $table = 'kaldik';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'category',
        'semester',
        'academic_year_id',
        'work_unit_id',
        'created_by',
        'type',
        'color',
        'start_date',
        'end_date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const CATEGORY_KALDIK = 'kaldik';

    const CATEGORY_AGENDA = 'agenda';

    const TYPE_TAHUNAN = 'tahunan';

    const TYPE_MID_SEMESTER = 'mid_semester';

    const TYPE_LAINNYA = 'lainnya';

    const TYPE_LIBUR = 'libur';

    const TYPE_UJIAN = 'ujian';

    const TYPE_KEGIATAN = 'kegiatan';

    const TYPE_HARI_EFEKTIF = 'hari_efektif';

    const SEMESTER_GANJIL = 'ganjil';

    const SEMESTER_GENAP = 'genap';

    const CATEGORY_OPTIONS = [
        self::CATEGORY_KALDIK => 'Kaldik (Pondok)',
        self::CATEGORY_AGENDA => 'Agenda Kegiatan',
    ];

    const TYPE_OPTIONS = [
        self::TYPE_TAHUNAN => 'Tahunan',
        self::TYPE_MID_SEMESTER => 'Mid Semester',
        self::TYPE_LIBUR => 'Libur',
        self::TYPE_UJIAN => 'Ujian / Sumatif',
        self::TYPE_KEGIATAN => 'Kegiatan Sekolah',
        self::TYPE_HARI_EFEKTIF => 'Hari Efektif',
        self::TYPE_LAINNYA => 'Lainnya',
    ];

    /** Tipe yang membuat hari menjadi tidak efektif (libur). */
    const NON_EFFECTIVE_TYPES = [self::TYPE_LIBUR];

    const SEMESTER_OPTIONS = [
        self::SEMESTER_GANJIL => 'Ganjil',
        self::SEMESTER_GENAP => 'Genap',
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

    // RELATIONSHIPS
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // SCOPES
    public function scopeKaldik($query)
    {
        return $query->where('category', self::CATEGORY_KALDIK);
    }

    public function scopeAgenda($query)
    {
        return $query->where('category', self::CATEGORY_AGENDA);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByWorkUnit($query, ?string $workUnitId)
    {
        if ($workUnitId) {
            return $query->where('work_unit_id', $workUnitId);
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

    // ACCESSORS
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => $value,
        );
    }
}
