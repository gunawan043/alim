<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentTransfer extends Model
{
    protected $table = 'student_transfers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'from_school_id',
        'to_school_id',
        'approved_by',
        'transfer_date',
        'reason',
        'transfer_type',
        'approval_status',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'from_school_id');
    }

    public function toSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'to_school_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTransferTypeTextAttribute(): string
    {
        return match ($this->transfer_type) {
            'masuk' => 'Pindahan Masuk',
            'keluar' => 'Pindahan Keluar',
            'pindah' => 'Pindah Internal',
            default => ucfirst($this->transfer_type ?? ''),
        };
    }
}
