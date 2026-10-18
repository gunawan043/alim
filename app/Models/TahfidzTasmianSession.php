<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TahfidzTasmianSession extends Model
{
    protected $table = 'tahfidz_tasmian_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id', 'school_id', 'academic_year_id', 'session_name', 'session_date',
        'session_time_start', 'session_time_end', 'location', 'session_type', 'status', 'created_by',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TahfidzTasmianParticipant::class, 'tasmian_session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(TahfidzTasmianScore::class, 'tasmian_session_id');
    }

    public function getSessionTypeLabelAttribute(): string
    {
        return match ($this->session_type) {
            'reguler' => 'Reguler',
            'kenaikan_juz' => 'Kenaikan Juz',
            'khatam' => 'Khatam',
            'khusus' => 'Khusus',
            default => ucfirst((string) $this->session_type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'terjadwal' => 'Terjadwal',
            'berlangsung' => 'Berlangsung',
            'selesai' => 'Selesai',
            default => ucfirst((string) $this->status),
        };
    }
}
