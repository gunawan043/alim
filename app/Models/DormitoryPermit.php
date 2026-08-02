<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryPermit extends Model
{
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
        'scanned_at',
        'last_actioned_by',
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
            'approved' => 'Disetujui / Menunggu Penjemputan',
            'rejected' => 'Ditolak',
            'returned' => 'Sudah Kembali ke Asrama',
            'overdue' => 'Telat Pulang',
            'picked_up' => 'Sudah Dijemput (Sedang Pulang)',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning-subtle text-warning',
            'approved' => 'bg-primary-subtle text-primary',
            'rejected' => 'bg-danger-subtle text-danger',
            'returned' => 'bg-success',
            'overdue' => 'bg-danger',
            'picked_up' => 'bg-info-subtle text-info',
            default => 'bg-secondary',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'approved'
            && $this->actual_return_datetime === null
            && $this->expected_return_datetime->isPast();
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
        if ($this->scan_token) {
            return $this->scan_token;
        }
        $token = (string) Str::ulid();
        // Sign it so it can't be forged
        $signature = hash_hmac('sha256', $token, config('app.key'));
        $signed = base64_encode($token.'::'.$signature);
        $this->update(['scan_token' => $signed]);

        return $signed;
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
                if ($permit) {
                    return $permit->student_id;
                }
            }
        }

        // Fallback: legacy tokens (plain ULID tanpa signature) mungkin masih ada di DB.
        $permit = static::whereNotNull('scan_token')->firstWhere(function ($q) use ($raw) {
            $q->where('scan_token', $raw);
        });

        if (! $permit) {
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
}
