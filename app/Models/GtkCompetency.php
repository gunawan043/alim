<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkCompetency extends Model
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
        'bidang_kompetensi',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // SCOPES
    public function scopeByBidang($query, $bidang)
    {
        return $query->where('bidang_kompetensi', $bidang);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ACCESSORS
    public function getStatusTextAttribute()
    {
        $statuses = [
            'AHLI' => 'Ahli',
            'INTERMEDIET' => 'Menengah',
            'BIASA' => 'Biasa',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'AHLI' => 'success',
            'INTERMEDIET' => 'warning',
            'BIASA' => 'info',
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}