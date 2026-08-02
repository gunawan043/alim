<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UksCareEvent extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'uks_care_events';

    protected $fillable = [
        'patient_id',
        'performed_by',
        'happened_at',
        'event_type',
        'event_title',
        'description',
    ];

    protected $casts = [
        'patient_id' => 'string',
        'performed_by' => 'string',
        'happened_at' => 'datetime',
    ];

    // ── Event type constants ────────────────────────────────────

    public const TYPE_MASUK = 'masuk';

    public const TYPE_PEMERIKSAAN = 'pemeriksaan';

    public const TYPE_PEMBERIAN_OBAT = 'pemberian_obat';

    public const TYPE_ISTIRAHAT = 'istirahat';

    public const TYPE_PEMERIKSAAN_ULANG = 'pemeriksaan_ulang';

    public const TYPE_PULANG = 'pulang';

    public const TYPE_DIRUJUK = 'dirujuk';

    public const TYPE_KEMBALI_ASRAMA = 'kembali_asrama';

    public const TYPE_KEMBALI_SEKOLAH = 'kembali_sekolah';

    public const TYPE_JEMPUT_WALI = 'jemput_wali';

    // ── Relationships ───────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uks\UksPatient::class, 'patient_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeForPatient($query, string $patientId)
    {
        return $query->where('patient_id', $patientId)->orderBy('happened_at');
    }

    /**
     * Get the icon class for a given event type.
     */
    public function eventTypeIcon(): string
    {
        return match ($this->event_type) {
            self::TYPE_MASUK => 'ri-login-box-line',
            self::TYPE_PEMERIKSAAN => 'ri-stethoscope-line',
            self::TYPE_PEMBERIAN_OBAT => 'ri-capsule-line',
            self::TYPE_ISTIRAHAT => 'ri-sleep-line',
            self::TYPE_PEMERIKSAAN_ULANG => 'ri-refresh-line',
            self::TYPE_PULANG => 'ri-logout-box-r-line',
            self::TYPE_DIRUJUK => 'ri-hospital-line',
            self::TYPE_KEMBALI_ASRAMA => 'ri-home-heart-line',
            self::TYPE_KEMBALI_SEKOLAH => 'ri-school-line',
            self::TYPE_JEMPUT_WALI => 'ri-user-heart-line',
            default => 'ri-information-line',
        };
    }

    /**
     * Human-readable label for a given event type.
     */
    public function eventTypeLabel(): string
    {
        return match ($this->event_type) {
            self::TYPE_MASUK => 'Masuk UKS',
            self::TYPE_PEMERIKSAAN => 'Pemeriksaan',
            self::TYPE_PEMBERIAN_OBAT => 'Pemberian Obat',
            self::TYPE_ISTIRAHAT => 'Istirahat',
            self::TYPE_PEMERIKSAAN_ULANG => 'Pemeriksaan Ulang',
            self::TYPE_PULANG => 'Pulang',
            self::TYPE_DIRUJUK => 'Dirujuk',
            self::TYPE_KEMBALI_ASRAMA => 'Kembali ke Asrama',
            self::TYPE_KEMBALI_SEKOLAH => 'Kembali ke Sekolah',
            self::TYPE_JEMPUT_WALI => 'Dijemput Wali',
            default => ucfirst($this->event_type),
        };
    }
}
