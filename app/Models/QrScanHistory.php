<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QrScanHistory extends Model
{
    use HasFactory;

    protected $table = 'qr_scan_histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'asset_id',
        'scanned_by',
        'scan_type',
        'lookup_value',
        'source',
        'ip_address',
        'user_agent',
        'purpose',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function scannedBy()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public $timestamps = false;

    public function getScannedAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->created_at;
    }
}
