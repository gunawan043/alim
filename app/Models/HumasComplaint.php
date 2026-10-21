<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumasComplaint extends Model
{
    use HasUuids;

    protected $table = 'humas_complaints';

    protected $fillable = ['school_id', 'submitted_by', 'category', 'subject', 'message', 'status', 'response', 'handled_by', 'handled_at'];

    protected $casts = ['handled_at' => 'datetime'];

    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function handler(): BelongsTo { return $this->belongsTo(User::class, 'handled_by'); }
}
