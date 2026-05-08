<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkTraining extends Model
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
        'bidang_pelatihan',
        'nama_pelatihan',
        'penyelenggara',
        'tahun',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'tahun' => 'integer',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // SCOPES
    public function scopeByBidang($query, $bidang)
    {
        return $query->where('bidang_pelatihan', $bidang);
    }

    public function scopeByTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    public function scopeRecent($query, $years = 5)
    {
        $currentYear = date('Y');
        return $query->where('tahun', '>=', $currentYear - $years);
    }

    // ACCESSORS
    public function getLamaPelatihanAttribute()
    {
        return $this->tahun ? now()->year - $this->tahun : null;
    }
}