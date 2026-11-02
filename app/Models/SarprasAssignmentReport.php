<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Laporan hasil penugasan Sarpras (work order perbaikan / pemeliharaan).
 * Satu work order = satu laporan, agar agenda pekerjaan yang sudah
 * dikerjakan langsung terlihat selesai.
 */
class SarprasAssignmentReport extends Model
{
    protected $table = 'sarpras_assignment_reports';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'work_order_id',
        'repair_request_id',
        'maintenance_schedule_id',
        'asset_id',
        'work_unit_id',
        'school_id',
        'reported_by',
        'report_date',
        'work_done',
        'obstacles',
        'follow_up',
        'parts_replaced',
        'cost',
        'condition_after',
        'duration_minutes',
        'status',
        'verified_by',
        'verified_at',
        'verification_note',
    ];

    protected $casts = [
        'report_date' => 'date',
        'cost' => 'decimal:2',
        'duration_minutes' => 'integer',
        'verified_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draf',
        self::STATUS_SUBMITTED => 'Menunggu verifikasi',
        self::STATUS_VERIFIED => 'Terverifikasi',
    ];

    /** Pilihan cepat kendala agar pengisian di lapangan tetap singkat. */
    public const OBSTACLE_PRESETS = [
        'Tidak ada kendala',
        'Menunggu suku cadang',
        'Butuh alat khusus',
        'Akses lokasi terbatas',
        'Cuaca / kondisi lapangan',
        'Butuh teknisi vendor',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = $model->id ?: (string) Str::uuid();
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(RepairRequest::class, 'repair_request_id');
    }

    public function maintenanceSchedule(): BelongsTo
    {
        return $this->belongsTo(AssetMaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'work_unit_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'success',
            self::STATUS_DRAFT => 'secondary',
            default => 'warning',
        };
    }
}
