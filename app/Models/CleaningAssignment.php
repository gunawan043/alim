<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CleaningAssignment extends Model
{
    protected $table = 'cleaning_assignments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'area_id', 'user_id', 'role', 'backup_user_id', 'supervisor_id',
        'start_date', 'end_date', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean',
    ];

    public function area(): BelongsTo { return $this->belongsTo(CleaningArea::class, 'area_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function backupUser(): BelongsTo { return $this->belongsTo(User::class, 'backup_user_id'); }
    public function supervisor(): BelongsTo { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
