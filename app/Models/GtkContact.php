<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GtkContact extends Model
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
        'no_hp',
        'no_whatsapp',
        'kontak_darurat',
        'instagram',
        'facebook',
        'twitter',
    ];

    protected $hidden = [
        'no_hp',
        'no_whatsapp',
        'kontak_darurat',
    ];

    protected $casts = [
        'id'      => 'string',
        'user_id' => 'string',
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

    /*
    |--------------------------------------------------------------------------
    | ENCRYPTED FIELDS
    |--------------------------------------------------------------------------
    */

    protected function noHp(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value, 
            set: fn (?string $value) => $value, 
        );
    }

    protected function noWhatsapp(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: fn (?string $value) => $value,
        );
    }

    protected function kontakDarurat(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: fn (?string $value) => $value,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MASKED ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getMaskedNoHpAttribute(): ?string
    {
        $val = $this->no_hp;
        return $val ? substr($val, 0, 4) . '****' . substr($val, -3) : null;
    }

    public function getMaskedNoWhatsappAttribute(): ?string
    {
        $val = $this->no_whatsapp;
        return $val ? substr($val, 0, 4) . '****' . substr($val, -3) : null;
    }

    public function getMaskedKontakDaruratAttribute(): ?string
    {
        $val = $this->kontak_darurat;
        return $val ? substr($val, 0, 4) . '****' . substr($val, -3) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getSocialMediaLinksAttribute(): array
    {
        $links = [];

        if ($this->instagram) {
            $links['instagram'] = 'https://instagram.com/' . ltrim($this->instagram, '@');
        }

        if ($this->facebook) {
            $links['facebook'] = 'https://facebook.com/' . $this->facebook;
        }

        if ($this->twitter) {
            $links['twitter'] = 'https://twitter.com/' . ltrim($this->twitter, '@');
        }

        return $links;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeHasSocialMedia($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('instagram')
              ->orWhereNotNull('facebook')
              ->orWhereNotNull('twitter');
        });
    }
}