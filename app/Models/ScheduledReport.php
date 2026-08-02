<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'format',
        'frequency',
        'recipients',
        'last_sent_at',
        'next_send_at',
        'is_active',
        'parameters',
    ];

    protected $casts = [
        'recipients' => 'array',
        'parameters' => 'array',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
