<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryPostResponse extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'dormitory_post_responses';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'post_id',
        'student_id',
        'parent_name',
        'response_type',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(DormitoryPost::class, 'post_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getResponseTypeTextAttribute(): string
    {
        return match ($this->response_type) {
            'ack'       => 'Konfirmasi',
            'question'  => 'Pertanyaan',
            'complaint' => 'Keluhan',
            default     => ucfirst($this->response_type ?? ''),
        };
    }
}
