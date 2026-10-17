<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GtkEducation extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'gtk_educations';

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
        'jenjang_pendidikan',
        'nama_satuan_pendidikan',
        'jurusan',
        'fakultas',
        'tahun_masuk',
        'tahun_lulus',
        'no_ijazah',
        'nama_kepala_sekolah',
        'nama_rektor',
        'nilai_akhir',
        'skala_nilai',
        'is_aktif',
        'status',
        'is_verified',
        'verified_at',
        'verified_by',
        'ijazah_path',
        'transkrip_path',
        'keterangan',
        'urutan',
    ];

    protected $hidden = [
        'no_ijazah',
        'ijazah_path',
        'transkrip_path',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'verified_by' => 'string',
        'tahun_masuk' => 'integer',
        'tahun_lulus' => 'integer',
        'nilai_akhir' => 'decimal:2',
        'is_aktif' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'urutan' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | CONSTANTS
    |--------------------------------------------------------------------------
    */

    const JENJANG = [
        'SD' => 'Sekolah Dasar',
        'SMP' => 'Sekolah Menengah Pertama',
        'SMA' => 'Sekolah Menengah Atas',
        'SMK' => 'Sekolah Menengah Kejuruan',
        'D1' => 'Diploma 1',
        'D2' => 'Diploma 2',
        'D3' => 'Diploma 3',
        'D4' => 'Diploma 4',
        'S1' => 'Sarjana (S1)',
        'S2' => 'Magister (S2)',
        'S3' => 'Doktor (S3)',
        'PAKET_B' => 'Paket B (Setara SMP)',
        'PAKET_C' => 'Paket C (Setara SMA)',
        'PROFESI' => 'Pendidikan Profesi',
        'SPESIALIS' => 'Spesialis',
    ];

    const STATUS = [
        'LULUS' => 'Lulus',
        'BELUM_LULUS' => 'Belum Lulus',
        'DROPOUT' => 'Drop Out',
        'PINDAH' => 'Pindah Sekolah',
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

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ENCRYPTED FIELDS
    |--------------------------------------------------------------------------
    */

    protected function noIjazah(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MASKED ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getMaskedNoIjazahAttribute(): ?string
    {
        $val = $this->no_ijazah;

        return $val ? substr($val, 0, 6).'/****/'.substr($val, -4) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getLamaPendidikanAttribute(): ?int
    {
        return ($this->tahun_masuk && $this->tahun_lulus)
            ? $this->tahun_lulus - $this->tahun_masuk
            : null;
    }

    public function getJenjangPendidikanTextAttribute(): string
    {
        return self::JENJANG[$this->jenjang_pendidikan] ?? $this->jenjang_pendidikan;
    }

    public function getStatusTextAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getNilaiAkhirFormattedAttribute(): ?string
    {
        if (! $this->nilai_akhir) {
            return null;
        }

        return number_format($this->nilai_akhir, 2);
    }

    public function getIpaAttribute(): ?float
    {
        if ($this->skala_nilai == '100' && $this->nilai_akhir) {
            return round(($this->nilai_akhir / 100) * 4, 2);
        }

        return $this->nilai_akhir;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeByJenjang($query, string $jenjang)
    {
        return $query->where('jenjang_pendidikan', $jenjang);
    }

    public function scopeByTahunLulus($query, int $tahun)
    {
        return $query->where('tahun_lulus', $tahun);
    }

    public function scopeHighestEducation($query)
    {
        return $query->orderByDesc('urutan')
            ->orderByDesc('tahun_lulus')
            ->limit(1);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICATION METHODS
    |--------------------------------------------------------------------------
    */

    public function verify(string $userId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'EDUCATION_VERIFIED',
            'table_name' => 'gtk_educations',
            'record_id' => $this->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function unverify(): void
    {
        $this->update([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'EDUCATION_UNVERIFIED',
            'table_name' => 'gtk_educations',
            'record_id' => $this->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
