<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkEmployment extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'school_id',
        'academic_year_id',
        'study_group_id',
        'status_kepegawaian',
        'nupy',
        'satuan_kerja',
        'jenis_gtk',
        'jabatan',
        'position_type',
        'is_homeroom',
        'jenis_gtk_id',
        'jabatan_id',
        'tmt',
        'nomor_sk',
        'decree_number',
        'tanggal_sk',
        'decree_date',
        'pangkat_golongan',
    ];

    protected $hidden = [
        'nupy',
        'nomor_sk',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'school_id' => 'string',
        'academic_year_id' => 'string',
        'study_group_id' => 'string',
        'tmt' => 'date',
        'tanggal_sk' => 'date',
        'decree_date' => 'date',
        'is_homeroom' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studyGroup()
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function jenisGtk()
    {
        return $this->belongsTo(JenisGtk::class, 'jenis_gtk_id');
    }

    public function jabatanRel()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ENCRYPTED FIELDS WITH SAFE DECRYPT (Try-Catch)
    |--------------------------------------------------------------------------
    */

    protected function nupy(): Attribute
    {
        // NUPY tidak dienkripsi karena bukan data rahasia
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: fn (?string $value) => $value,
        );
    }

    protected function nomorSk(): Attribute
    {
        // nomor_sk tidak dienkripsi
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: fn (?string $value) => $value,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS (Cek Status Enkripsi)
    |--------------------------------------------------------------------------
    */

    /**
     * Cek apakah field nupy sudah terenkripsi
     */
    public function isNupyEncrypted(): bool
    {
        $raw = $this->getRawOriginal('nupy');

        return $raw && str_starts_with($raw, 'eyJpdiI6');
    }

    /**
     * Cek apakah field nomor_sk sudah terenkripsi
     */
    public function isNomorSkEncrypted(): bool
    {
        $raw = $this->getRawOriginal('nomor_sk');

        return $raw && str_starts_with($raw, 'eyJpdiI6');
    }

    /**
     * Dapatkan nilai asli (raw) dari nupy tanpa decrypt
     */
    public function getRawNupy(): ?string
    {
        return $this->getRawOriginal('nupy');
    }

    /**
     * Dapatkan nilai asli (raw) dari nomor_sk tanpa decrypt
     */
    public function getRawNomorSk(): ?string
    {
        return $this->getRawOriginal('nomor_sk');
    }

    /*
    |--------------------------------------------------------------------------
    | MASKED ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getMaskedNupyAttribute(): ?string
    {
        $val = $this->nupy; // Sudah otomatis decrypt atau plain
        if (! $val) {
            return null;
        }

        // Masking: 4 karakter awal + **** + 2 karakter akhir
        if (strlen($val) <= 6) {
            return substr($val, 0, 2).'****';
        }

        return substr($val, 0, 4).'****'.substr($val, -2);
    }

    public function getMaskedNomorSkAttribute(): ?string
    {
        $val = $this->nomor_sk; // Sudah otomatis decrypt atau plain
        if (! $val) {
            return null;
        }

        // Masking untuk format SK: contoh "1234/SK/2024" -> "1234/****/2024"
        if (strlen($val) <= 10) {
            return substr($val, 0, 4).'/****/'.substr($val, -4);
        }

        return substr($val, 0, 6).'/****/'.substr($val, -4);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatusKepegawaianTextAttribute(): string
    {
        return self::STATUS_LABELS[$this->status_kepegawaian] ?? $this->status_kepegawaian;
    }

    public function getMasaKerjaAttribute(): ?int
    {
        if (! $this->tmt) {
            return null;
        }

        return $this->tmt->diffInYears(now());
    }

    public function getMasaKerjaDetailAttribute(): ?string
    {
        if (! $this->tmt) {
            return null;
        }

        $years = $this->tmt->diffInYears(now());
        $months = $this->tmt->diffInMonths(now()) % 12;
        $days = $this->tmt->diffInDays(now()) % 30;

        $parts = [];
        if ($years > 0) {
            $parts[] = "{$years} tahun";
        }
        if ($months > 0) {
            $parts[] = "{$months} bulan";
        }
        if ($days > 0 && $years == 0) {
            $parts[] = "{$days} hari";
        }

        return implode(' ', $parts) ?: 'Kurang dari 1 bulan';
    }

    public function getTanggalSkFormattedAttribute(): ?string
    {
        return $this->tanggal_sk ? $this->tanggal_sk->format('d/m/Y') : null;
    }

    public function getTmtFormattedAttribute(): ?string
    {
        return $this->tmt ? $this->tmt->format('d/m/Y') : null;
    }

    /*
    |--------------------------------------------------------------------------
    | CONSTANTS
    |--------------------------------------------------------------------------
    */

    const STATUS_LABELS = [
        'PTT' => 'Pegawai Tidak Tetap',
        'PTY' => 'Pegawai Tetap Yayasan',
        'Percobaan' => 'Masa Percobaan',
        'Magang' => 'Magang',
        'GTT' => 'Guru Tidak Tetap',
        'GTY' => 'Guru Tetap Yayasan',
        'KONTRAK' => 'Kontrak',
    ];

    const STATUS_LIST = [
        'PTT',
        'PTY',
        'Percobaan',
        'Magang',
        'GTT',
        'GTY',
        'KONTRAK',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status_kepegawaian', $status);
    }

    public function scopeBySatuanKerja($query, string $satuanKerja)
    {
        return $query->where('satuan_kerja', $satuanKerja);
    }

    public function scopeByJenisGtk($query, string $jenisGtk)
    {
        return $query->where('jenis_gtk', $jenisGtk);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('tmt');
    }

    public function scopeByPangkatGolongan($query, string $pangkat)
    {
        return $query->where('pangkat_golongan', $pangkat);
    }
}
