<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CutiBalance extends Model
{
    use HasUuids;

    protected $table = 'cuti_balances';

    protected $fillable = [
        'user_id',
        'cuti_template_id',
        'cuti_period_id',
        'jumlah_hari',
        'digunakan',
        'tersisa',
        'notes',
    ];

    protected $casts = [
        'jumlah_hari' => 'integer',
        'digunakan' => 'integer',
        'tersisa' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(CutiTemplate::class, 'cuti_template_id');
    }

    public function period()
    {
        return $this->belongsTo(CutiPeriod::class, 'cuti_period_id');
    }
}
