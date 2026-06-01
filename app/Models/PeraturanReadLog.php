<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeraturanReadLog extends Model
{
    use HasUuids;
    protected $table = 'peraturan_read_log';

    protected $fillable = ['peraturan_id', 'user_id', 'read_at', 'ip_address'];
    protected $casts = ['read_at' => 'datetime'];

    public function peraturan(): BelongsTo { return $this->belongsTo(Peraturan::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
