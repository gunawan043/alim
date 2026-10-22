<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DormitoryQrScanLog extends Model
{
    protected $table = 'dormitory_qr_scan_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id = $model->id ?: (string) Str::uuid();
        });
    }

    protected $fillable = [
        'dormitory_id',
        'permit_id',
        'visit_id',
        'user_id',
        'action',
        'result',
        'reason',
        'ip_address',
        'user_agent',
    ];

    public function permit()
    {
        return $this->belongsTo(DormitoryPermit::class, 'permit_id');
    }

    public function visit()
    {
        return $this->belongsTo(DormitoryVisitLog::class, 'visit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
