<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockOpnameOfficer extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_officers';

    protected $fillable = [
        'session_id',
        'user_id',
        'role',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function session()
    {
        return $this->belongsTo(StockOpnameSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}