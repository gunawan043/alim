<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
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
    ];

    protected $casts = [
        'expected_arrival_datetime' => 'datetime',
        'actual_arrival_datetime' => 'datetime',
        'departure_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'expected_meet_duration_minutes' => 'integer',
        'visitor_access_rights' => 'array',
        'is_special_permission' => 'boolean',
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
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'arrived' => 'Sudah Tiba',
            'checked_out' => 'Sudah Pulang',
            'cancelled' => 'Dibatalkan',
            'no_show' => 'Tidak Datang',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getVisitorRelationshipTextAttribute(): string
    {
        return match ($this->visitor_relationship) {
            'mahrom' => 'Mahrom',
            'wali' => 'Wali Santri',
            'keluarga' => 'Keluarga',
            'Pihak pondok' => 'Pihak pondok',
            ' Lainnya' => ' Lainnya',
            default => ucfirst($this->visitor_relationship ?? ''),
        };
    }

    public function getPurposeTextAttribute(): string
    {
        return match ($this->purpose) {
            'menjenguk' => 'Menjenguk',
            'bawa_bantuan' => 'Bawa Bantuan',
            'pertemuan_wali' => 'Pertemuan Wali',
            'antar_jemput' => 'Antar/Jemput',
            'lainnya' => ' Lainnya',
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
