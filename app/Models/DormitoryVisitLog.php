<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DormitoryVisitLog extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id',
        'room_id',
        'student_id',
        'visitor_name',
        'visitor_id_number',
        'visitor_phone',
        'visitor_count',
        'visitor_gender',
        'visitor_relationship',
        'purpose',
        'expected_arrival_datetime',
        'actual_arrival_datetime',
        'departure_datetime',
        'expected_meet_duration_minutes',
        'notes',
        'approved_by',
        'approved_at',
        'approval_note',
        'check_in_at',
        'check_out_at',
        'status',
        'created_by',
        'mahrom_id',
        'visitor_access_rights',
        'is_special_permission',
        'special_reason',
        'identity_verified_at',
        'identity_verified_by',
        'card_verified_at',
        'card_verified_by',
        'rules_acknowledged_at',
        'schedule_exception_reason',
        'schedule_exception_approved_by',
        'schedule_exception_approved_at',
        'linked_permit_id',
    ];

    protected $casts = [
        'expected_arrival_datetime' => 'datetime',
        'actual_arrival_datetime' => 'datetime',
        'departure_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'expected_meet_duration_minutes' => 'integer',
        'visitor_count' => 'integer',
        'visitor_access_rights' => 'array',
        'is_special_permission' => 'boolean',
        'identity_verified_at' => 'datetime',
        'card_verified_at' => 'datetime',
        'rules_acknowledged_at' => 'datetime',
        'schedule_exception_approved_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mahrom(): BelongsTo
    {
        return $this->belongsTo(StudentMahrom::class, 'mahrom_id');
    }

    /**
     * Check if visitor has access to restricted areas.
     */
    public function canAccessRestrictedAreas(): bool
    {
        return $this->hasPermission('restricted_areas', true);
    }

    /**
     * Check if overnight stay is allowed.
     */
    public function canStayOvernight(): bool
    {
        return $this->hasPermission('can_stay_overnight', false);
    }

    /**
     * Get guardian supervision requirement flag.
     */
    public function requiresGuardianSupervision(): bool
    {
        return $this->hasPermission('guardian_supervision_required', true);
    }

    /**
     * Get max visitor count allowed.
     */
    public function getMaxVisitors(): int
    {
        return (int) ($this->visitor_access_rights['max_visitor_count'] ?? 3);
    }

    private function hasPermission(string $key, mixed $default = false): bool
    {
        return $this->visitor_access_rights[$key] ?? $default;
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Santri Belum Keluar',
            'rejected' => 'Ditolak',
            'arrived' => 'Sedang dalam Kunjungan',
            'checked_out' => 'Kunjungan Selesai',
            'cancelled' => 'Dibatalkan',
            'no_show' => 'Tidak Datang',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        $class = match ($this->status) {
            'pending' => 'bg-warning-subtle text-warning',
            'approved' => 'bg-info-subtle text-info',
            'arrived' => 'bg-warning-subtle text-warning',
            'checked_out' => 'bg-success-subtle text-success',
            'rejected', 'cancelled', 'no_show' => 'bg-danger-subtle text-danger',
            default => 'bg-secondary-subtle text-secondary',
        };

        return '<span class="badge '.$class.'">'.e($this->status_text).'</span>';
    }

    public function getMaskedVisitorPhoneAttribute(): string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->visitor_phone);

        if (strlen($phone) <= 4) {
            return $this->visitor_phone ?: '—';
        }

        return substr($phone, 0, 4).'••••'.substr($phone, -3);
    }

    public function getMaskedVisitorIdNumberAttribute(): string
    {
        $id = trim((string) $this->visitor_id_number);

        if ($id === '') {
            return '—';
        }

        if (strlen($id) <= 6) {
            return substr($id, 0, 2).'••••';
        }

        return substr($id, 0, 4).'••••'.substr($id, -3);
    }

    public function getVisitorRelationshipTextAttribute(): string
    {
        return match (strtolower(trim((string) $this->visitor_relationship))) {
            'mahrom' => 'Mahrom',
            'wali' => 'Wali Santri',
            'keluarga' => 'Keluarga',
            'pihak_pondok', 'pihak pondok' => 'Pihak Pondok',
            'lainnya', ' Lainnya' => 'Lainnya',
            default => ucfirst(str_replace('_', ' ', (string) ($this->visitor_relationship ?? ''))),
        };
    }

    /**
     * Alias tanggal agar view lama dan view baru memakai sumber data yang sama.
     */
    public function getExpectedArrivalAttribute()
    {
        return $this->expected_arrival_datetime;
    }

    public function getActualArrivalAtAttribute()
    {
        return $this->check_in_at ?? $this->actual_arrival_datetime;
    }

    public function getActualDepartureAtAttribute()
    {
        return $this->check_out_at ?? $this->departure_datetime;
    }

    public function getExpectedDurationMinutesAttribute()
    {
        return $this->expected_meet_duration_minutes;
    }

    /**
     * Masa berlaku kartu QR kunjungan. Kartu harus tetap bisa dipakai
     * sampai santri kembali, dengan toleransi 1 hari.
     */
    public function cardExpiresAt(): CarbonInterface
    {
        if ($this->check_out_at) {
            return $this->check_out_at->copy()->addDay();
        }

        $base = $this->expected_arrival_datetime?->copy() ?? now();
        $minutes = max(5, (int) ($this->expected_meet_duration_minutes ?? 60));

        return $base->addMinutes($minutes)->addDay();
    }

    public function makeCardToken(): string
    {
        $expires = $this->cardExpiresAt()->getTimestamp();
        $payload = $this->id.'::'.$expires;
        $signature = hash_hmac('sha256', $payload, (string) config('app.key'));

        return base64_encode($payload.'::'.$signature);
    }

    public function getPurposeTextAttribute(): string
    {
        return match ($this->purpose) {
            'menjenguk' => 'Menjenguk',
            'bawa_bantuan' => 'Bawa Bantuan',
            'pertemuan_wali' => 'Pertemuan Wali',
            'antar_jemput' => 'Antar/Jemput',
            'lainnya' => 'Lainnya',
            default => ucfirst($this->purpose ?? ''),
        };
    }

    /**
     * Get scan token attribute (not used for visits, but kept for compatibility with card view).
     */
    public function getScanTokenAttribute()
    {
        // The visits table does not have a scan_token column; returning null.
        return null;
    }
}
