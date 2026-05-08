<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GtkProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'nik',
        'no_kk',
        'tempat_lahir',
        'tanggal_lahir',
        'nama_ibu_kandung',
        'golongan_darah',
        'jenis_kelamin',
        'agama',
        'status_perkawinan',
        'npwp',
        // Catatan: work_unit_id, jabatan, tmt_kerja, no_hp, no_whatsapp, kontak_darurat
        // TIDAK ada di sini — sudah dikelola di GtkWorkUnit, GtkEmployment, GtkContact
    ];

    protected $hidden = [
        'nik',
        'no_kk',
        'nama_ibu_kandung',
        'npwp',
    ];

    protected $casts = [
        'id'           => 'string',
        'user_id'      => 'string',
        'tanggal_lahir' => 'date',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        static::created(function ($profile) {
            AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'GTK_PROFILE_CREATED',
                'table_name' => 'gtk_profiles',
                'record_id'  => $profile->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updated(function ($profile) {
            AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'GTK_PROFILE_UPDATED',
                'table_name' => 'gtk_profiles',
                'record_id'  => $profile->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::deleted(function ($profile) {
            AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'GTK_PROFILE_DELETED',
                'table_name' => 'gtk_profiles',
                'record_id'  => $profile->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addresses()
    {
        return $this->hasMany(GtkAddress::class);
    }

    public function domisiliAddress()
    {
        return $this->hasOne(GtkAddress::class)->where('type', 'domisili');
    }

    public function ktpAddress()
    {
        return $this->hasOne(GtkAddress::class)->where('type', 'ktp');
    }

    public function familyMembers()
    {
        return $this->hasMany(GtkFamilyMember::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ENCRYPTED FIELDS
    |--------------------------------------------------------------------------
    */

    protected function nik(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function noKk(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function namaIbuKandung(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function npwp(): Attribute
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

    public function getMaskedNikAttribute(): ?string
    {
        $val = $this->nik;
        return $val ? substr($val, 0, 6) . '****' . substr($val, -4) : null;
    }

    public function getMaskedNoKkAttribute(): ?string
    {
        $val = $this->no_kk;
        return $val ? substr($val, 0, 6) . '****' . substr($val, -4) : null;
    }

    public function getMaskedNpwpAttribute(): ?string
    {
        $val = $this->npwp;
        return $val ? substr($val, 0, 6) . '****' . substr($val, -3) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getAgeAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->whereHas('user', fn ($q) => $q->where('is_active', true));
    }
}