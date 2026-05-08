<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GtkAdditionalTask extends Model
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
        'decree_id',
        'nama_tugas',
        'hours_per_week',
        'nomor_sk',
        'tmt',
        'tst',
    ];

    protected $hidden = [
        'nomor_sk',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'tmt' => 'date',
        'tst' => 'date',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function decree()
    {
        return $this->belongsTo(InstitutionDecree::class, 'decree_id');
    }

    // ENCRYPTED FIELDS
    protected function nomorSk(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    // ACCESSORS
    public function getMasaTugasAttribute()
    {
        if ($this->tmt && $this->tst) {
            return $this->tmt->diffInMonths($this->tst);
        }
        return null;
    }

    public function getIsActiveAttribute()
    {
        if (!$this->tst) return true;
        return now()->lessThanOrEqualTo($this->tst);
    }

    // MASKED ACCESSOR
    public function getMaskedNomorSkAttribute()
    {
        $nomorSk = $this->nomor_sk;
        return $nomorSk ? substr($nomorSk, 0, 6) . '/****/' . substr($nomorSk, -4) : null;
    }
}