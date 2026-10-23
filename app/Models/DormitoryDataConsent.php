<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryDataConsent extends Model
{
    protected $table = 'dormitory_data_consents';

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
        'student_id',
        'guardian_name',
        'relationship',
        'phone',
        'consent_version',
        'consented_at',
        'revoked_at',
        'recorded_by',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public static function activeFor(string $studentId, string $dormitoryId): ?self
    {
        return static::query()
            ->where('student_id', $studentId)
            ->where('dormitory_id', $dormitoryId)
            ->whereNull('revoked_at')
            ->latest('consented_at')
            ->first();
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
