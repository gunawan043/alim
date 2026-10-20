<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Supervisi extends Model
{
    use LogsDeletion;
    use SoftDeletes;

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
        'instrumen_id', 'skor_total', 'skor_maks', 'nilai_akhir', 'ketercapaian',
        'jadwal_kbm_id', 'study_group_id', 'subject_id', 'slot_index',
        'reported_to_wadir_at', 'reflection_notes', 'distributed_at', 'distributed_by',
    ];

    protected $casts = [
        'semester' => 'integer',
        'tanggal_supervisi' => 'date',
        'distributed_at' => 'datetime',
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

    public function instrumen(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SupervisiInstrumen::class, 'instrumen_id');
    }

    public function penilaian(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupervisiPenilaian::class, 'supervisi_id');
    }

    public function observer()
    {
        return $this->belongsTo(GtkProfile::class, 'observer_id');
    }

    public function jadwalKbm()
    {
        return $this->belongsTo(JadwalKbm::class, 'jadwal_kbm_id');
    }

    public function studyGroup()
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
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
