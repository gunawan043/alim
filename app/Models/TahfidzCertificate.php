<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzCertificate extends Model
{
    protected $table = 'tahfidz_certificates';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'class_name', 'school_id', 'academic_year_id', 'tasmian_participant_id',
        'certificate_type', 'certificate_number', 'issued_date', 'total_juz_completed', 'materi',
        'juz_detail', 'nilai_akhir', 'predikat', 'golongan', 'nama_lembaga',
        'kode_lembaga', 'kode_bulan_tahun_cetak', 'issued_by', 'ceremony_date',
        'file_path', 'is_printed', 'printed_at', 'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'ceremony_date' => 'date',
        'juz_detail' => 'array',
        'total_juz_completed' => 'integer',
        'nilai_akhir' => 'decimal:2',
        'is_printed' => 'boolean',
        'printed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TahfidzTasmianParticipant::class, 'tasmian_participant_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getCertificateTypeLabelAttribute(): string
    {
        return match ($this->certificate_type) {
            'juz_tunggal' => 'Juz Tunggal',
            '5_juz' => '5 Juz',
            '10_juz' => '10 Juz',
            '15_juz' => '15 Juz',
            '20_juz' => '20 Juz',
            '25_juz' => '25 Juz',
            '30_juz' => '30 Juz',
            'khatam' => 'Khatam',
            default => ucfirst((string) $this->certificate_type),
        };
    }

    public function getPredikatLabelAttribute(): string
    {
        return match ($this->predikat) {
            'mumtaz' => 'Mumtaz',
            'jayyid_jiddan' => 'Jayyid Jiddan',
            'jayyid' => 'Jayyid',
            'maqbul' => 'Maqbul',
            'rasib' => 'Rasib',
            default => $this->predikat ?: '—',
        };
    }

    public function getPredikatArabicAttribute(): string
    {
        return match ($this->predikat) {
            'mumtaz' => 'ممتاز',
            'jayyid_jiddan' => 'جيد جدًا',
            'jayyid' => 'جيد',
            'maqbul' => 'مقبول',
            'rasib' => 'راسب',
            default => '—',
        };
    }

    public function getStudentNameArabicAttribute(): string
    {
        return $this->student?->name_ar ?: '—';
    }
}
