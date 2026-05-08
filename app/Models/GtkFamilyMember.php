<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GtkFamilyMember extends Model
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
        'gtk_profile_id',
        'relationship',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pekerjaan',
        'pendidikan_terakhir',
        'alamat',
    ];

    protected $hidden = [
        'alamat',
        'tanggal_lahir',
    ];

    protected $casts = [
        'id'             => 'string',
        'gtk_profile_id' => 'string',
        'tanggal_lahir'  => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | CONSTANTS
    |--------------------------------------------------------------------------
    */

    const RELATIONSHIP_LABELS = [
        'suami' => 'Suami',
        'istri'  => 'Istri',
        'anak'   => 'Anak',
        'ayah'   => 'Ayah',
        'ibu'    => 'Ibu',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function gtkProfile()
    {
        return $this->belongsTo(GtkProfile::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ENCRYPTED FIELDS
    |--------------------------------------------------------------------------
    */

    protected function alamat(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getUmurAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    public function getRelationshipTextAttribute(): string
    {
        return self::RELATIONSHIP_LABELS[$this->relationship] ?? $this->relationship;
    }

    public function getJenisKelaminTextAttribute(): string
    {
        return match($this->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }
}