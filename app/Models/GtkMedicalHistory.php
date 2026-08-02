<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GtkMedicalHistory extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'gtk_medical_histories';

    protected $fillable = [
        'user_id',
        'history_conditions',
        'hypertension',
        'diabetes',
        'asthma',
        'heart_disease',
        'kidney_disease',
        'hepatitis',
        'tb',
        'allergies',
        'allergy_details',
        'other_conditions',
        'regular_medications',
        'surgery_history',
        'hospitalization_history',
        'accident_history',
    ];

    protected $casts = [
        'user_id' => 'string',
        'history_conditions' => 'array',
        'regular_medications' => 'array',
        'surgery_history' => 'array',
        'hospitalization_history' => 'array',
        'accident_history' => 'array',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(GtkHealthRecord::class, 'medical_history_id');
    }

    /**
     * Get or create a single per-user medical history record.
     */
    public static function forUser(string|int $userId): self
    {
        if (is_int($userId)) {
            $userId = (string) $userId;
        }

        return self::firstOrCreate(['user_id' => $userId]);
    }
}
