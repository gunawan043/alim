<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\LogsDeletion;
use Illuminate\Support\Str;

class Supervisi extends Model
{
    use SoftDeletes;
    use LogsDeletion;

    protected $table = 'supervisi';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'gtk_id',
        'observer_id',
        'gtk_name',
        'observer_name',
        'semester',
        'mata_pelajaran',
        'tanggal_supervisi',
        'jam_mulai',
        'jam_selesai',
        'jenis_supervisi',
        'tujuan',
        'catatan_temuan',
        'rekomendasi',
        'tindak_lanjut',
        'status',
    ];

    protected $casts = [
        'semester' => 'integer',
        'tanggal_supervisi' => 'date',
    ];

    const JENIS_PERANGKAT = 'perangkat_pembelajaran';
    const JENIS_PROSES = 'proses_pembelajaran';
    const JENIS_PENILAIAN = 'penilaian';
    const JENIS_LAINNYA = 'lainnya';

    const JENIS_OPTIONS = [
        self::JENIS_PERANGKAT => 'Perangkat Pembelajaran',
        self::JENIS_PROSES => 'Proses Pembelajaran',
        self::JENIS_PENILAIAN => 'Penilaian',
        self::JENIS_LAINNYA => 'Lainnya',
    ];

    const STATUS_TERJADWAL = 'terjadwal';
    const STATUS_BERLANGSUNG = 'berlangsung';
    const STATUS_SELESAI = 'selesai';
    const STATUS_DIBATALKAN = 'dibatalkan';

    const STATUS_OPTIONS = [
        self::STATUS_TERJADWAL => 'Terjadwal',
        self::STATUS_BERLANGSUNG => 'Berlangsung',
        self::STATUS_SELESAI => 'Selesai',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
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

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function gtk()
    {
        return $this->belongsTo(GtkProfile::class, 'gtk_id');
    }

    public function observer()
    {
        return $this->belongsTo(GtkProfile::class, 'observer_id');
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

    public function scopeByStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_TERJADWAL, self::STATUS_BERLANGSUNG]);
    }
}