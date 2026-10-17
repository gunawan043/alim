<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryGatepass extends Model
{
    protected $table = 'dormitory_gatepasses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'dormitory_id', 'student_id', 'type', 'purpose', 'gate', 'notes', 'scanned_by', 'scanned_at',
    ];

    protected $casts = ['scanned_at' => 'datetime'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
