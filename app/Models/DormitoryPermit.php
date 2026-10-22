<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryPermit extends Model
{
    // ── State machine izin (satu sumber kebenaran) ────────────────
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Transisi status yang sah: pending → approved/rejected → (scan keluar)
     * picked_up → (scan kembali) returned. Returned/rejected bersifat terminal.
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_REJECTED],
        self::STATUS_APPROVED => [self::STATUS_OVERDUE, self::STATUS_REJECTED, self::STATUS_PICKED_UP, self::STATUS_RETURNED],
        self::STATUS_OVERDUE => [self::STATUS_REJECTED, self::STATUS_PICKED_UP, self::STATUS_RETURNED],
        self::STATUS_PICKED_UP => [self::STATUS_RETURNED],
        self::STATUS_RETURNED => [],
        self::STATUS_REJECTED => [],
    ];

    public function canTransitionTo(string $newStatus): bool
    {
        if ($newStatus === $this->status) {
            return true; // idempoten
        }

        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Fase izin untuk tampilan:
     * menunggu → belum_keluar → sedang_keluar → kembali.
     */
    public function phase(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'menunggu',
            self::STATUS_APPROVED, self::STATUS_OVERDUE => 'belum_keluar',
            self::STATUS_PICKED_UP => 'sedang_keluar',
            self::STATUS_RETURNED => 'kembali',
            self::STATUS_REJECTED => 'ditolak',
            default => $this->status,
        };
    }

    protected $table = 'dormitory_permits';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'dormitory_id',
        'room_id',
        'academic_year_id',
        'permit_type',
        'destination',
        'purpose',
        'departure_datetime',
        'expected_return_datetime',
        'actual_return_datetime',
        'companion_name',
        'companion_relation',
        'companion_phone',
        'companion_is_mahrom',
        'mahrom_id',
        'status',
        'secondary_status',
        'approved_by',
        'approved_at',
        'approval_note',
        'document_path',
        'overdue_notified_count',
        'overdue_notified_at',
        'escalation_triggered_at',
        'created_by',
        'is_special_permission',
        'special_reason',
        'is_emergency',
        'emergency_contact_name',
        'emergency_contact_phone',
        'pickup_details',
        'return_details',
        'scan_token',
        'scan_token_expires_at',
        'scan_token_revoked_at',
        'scanned_at',
        'last_actioned_by',
        'pickup_authorization_status',
        'pickup_authorization_note',
        'pickup_authorized_by',
        'pickup_authorized_at',
        'pickup_scanned_at',
        'pickup_scanned_by',
        'return_scanned_at',
        'return_scanned_by',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'expected_return_datetime' => 'datetime',
        'actual_return_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'escalation_triggered_at' => 'datetime',
        'scanned_at' => 'datetime',
        'scan_token_expires_at' => 'datetime',
        'scan_token_revoked_at' => 'datetime',
        'pickup_authorized_at' => 'datetime',
        'pickup_scanned_at' => 'datetime',
        'return_scanned_at' => 'datetime',
        'overdue_notified_count' => 'integer',
        'is_special_permission' => 'boolean',
        'is_emergency' => 'boolean',
        'companion_is_mahrom' => 'boolean',
        'pickup_details' => 'array',
        'return_details' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
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
        return $this->belongsTo(StudentMahrom::class);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getPermitTypeTextAttribute(): string
    {
        return match ($this->permit_type) {
            'pulang' => 'Pulang',
            'keluar_kota' => 'Keluar Kota',
            'berobat' => 'Berobat',
            'sakit' => 'Sakit',
            'keperluan_keluarga' => 'Keperluan Keluarga',
            'lainnya' => 'Lainnya',
            default => ucfirst($this->permit_type ?? ''),
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Belum Keluar',
            'rejected' => 'Ditolak',
            'returned' => 'Sudah Kembali',
            'overdue' => 'Belum Keluar — Melewati Jadwal',
            'picked_up' => 'Sedang di Luar Asrama',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning-subtle text-warning',
            'approved' => 'bg-info-subtle text-info',
            'rejected' => 'bg-danger-subtle text-danger',
            'returned' => 'bg-success',
            'overdue' => 'bg-danger',
            'picked_up' => 'bg-warning-subtle text-warning',
            default => 'bg-secondary',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_OVERDUE], true)
            && $this->actual_return_datetime === null
            && $this->expected_return_datetime?->isPast() === true;
    }

    // ── Emergency accessors ───────────────────────────────────────

    public function getEmergencyContactTextAttribute(): string
    {
        $parts = array_filter([
            $this->emergency_contact_name,
            $this->emergency_contact_phone ? '('.$this->emergency_contact_phone.')' : null,
        ]);

        return implode(' — ', $parts) ?: '—';
    }

    public function getPicketModeAttribute(): ?string
    {
        return $this->pickup_details['mode'] ?? null;
    }

    public function getLastActionedBy(): ?User
    {
        if (! $this->last_actioned_by) {
            return null;
        }

        return User::find($this->last_actioned_by);
    }

    // ── QR / Scan Token ───────────────────────────────────────────

    /**
     * Generate or return an existing signed scan token.
     */
    public function getOrCreateScanToken(): string
    {
        if ($this->scan_token && ! $this->isScanTokenExpired() && ! $this->scan_token_revoked_at) {
            // Token lama (sebelum kolom expiry ada) tetap diberi masa berlaku.
            if (! $this->scan_token_expires_at) {
                $this->update(['scan_token_expires_at' => $this->defaultScanTokenExpiry()]);
            }

            return $this->scan_token;
        }

        $token = (string) Str::ulid();
        // Sign it so it can't be forged.
        $signature = hash_hmac('sha256', $token, config('app.key'));
        $signed = base64_encode($token.'::'.$signature);

        $this->update([
            'scan_token' => $signed,
            'scan_token_expires_at' => $this->defaultScanTokenExpiry(),
            'scan_token_revoked_at' => null,
        ]);

        return $signed;
    }

    public function isScanTokenExpired(): bool
    {
        return $this->scan_token_expires_at !== null
            && $this->scan_token_expires_at->isPast();
    }

    public function defaultScanTokenExpiry(): \Carbon\CarbonInterface
    {
        return $this->expected_return_datetime
            ? $this->expected_return_datetime->copy()->addDays(2)
            : now()->addDays(30);
    }

    public function revokeScanToken(?string $reason = null): void
    {
        $this->update([
            'scan_token_revoked_at' => now(),
            'pickup_authorization_note' => $reason
                ? trim(($this->pickup_authorization_note ? $this->pickup_authorization_note."\n" : '').$reason)
                : $this->pickup_authorization_note,
        ]);
    }

    public function needsPickupAuthorization(): bool
    {
        return $this->pickup_authorization_status === 'pending';
    }

    public function isPickupRejected(): bool
    {
        return $this->pickup_authorization_status === 'rejected';
    }

    /**
     * Verify whether a raw token string matches this permit's signed scan token.
     * Returns the student_id if valid, null otherwise.
     */
    public static function verifyScanToken(string $raw): ?string
    {
        $raw = trim($raw);
        $decoded = base64_decode($raw, true);

        if ($decoded && str_contains($decoded, '::')) {
            [$token, $providedSig] = explode('::', $decoded, 2);
            $expectedSig = hash_hmac('sha256', $token, config('app.key'));

            if (hash_equals($expectedSig, $providedSig)) {
                $permit = static::whereNotNull('scan_token')->firstWhere(function ($q) use ($raw) {
                    $q->where('scan_token', $raw);
                });
                if ($permit && ! $permit->scan_token_revoked_at && ! $permit->isScanTokenExpired()) {
                    return $permit->student_id;
                }
            }
        }

        // Fallback: legacy tokens (plain ULID tanpa signature) mungkin masih ada di DB.
        $permit = static::whereNotNull('scan_token')->firstWhere(function ($q) use ($raw) {
            $q->where('scan_token', $raw);
        });

        if (! $permit || $permit->scan_token_revoked_at || $permit->isScanTokenExpired()) {
            return null;
        }

        return $permit->student_id;
    }

    /**
     * The base64-encoded payload that mobile apps will render as QR + store for scanning.
     */
    public function qrPayload(): ?array
    {
        if (! $this->scan_token) {
            $this->getOrCreateScanToken();
        }

        return [
            'token' => $this->scan_token,
            'student_id' => $this->student_id,
            'student_name' => $this->student?->name,
            'permit_id' => $this->id,
            'permit_type' => $this->permit_type,
            'is_emergency' => (bool) $this->is_emergency,
            'status' => $this->status,
            'dormitory_id' => $this->dormitory_id,
            'expires_at' => $this->scan_token_expires_at?->toIso8601String(),
        ];
    }

    /**
     * Returns the URL the mobile app should open to scan this QR.
     */
    public function qrUrl(string $baseUrl): string
    {
        $token = $this->scan_token ?: $this->getOrCreateScanToken();

        return "{$baseUrl}/permits/verify?t=".urlencode($token);
    }

    // ── Relationships (additional) ─────────────────────────────────

    public function lastActionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_actioned_by');
    }

    public function pickupAuthorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pickup_authorized_by');
    }
}
