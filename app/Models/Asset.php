<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory, LogsDeletion, SoftDeletes;

    protected $table = 'assets';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Asset $asset) {
            $asset->id = $asset->id ?: (string) Str::uuid();

            if (blank($asset->work_unit_sequence)) {
                $asset->work_unit_sequence = (int) static::withTrashed()
                    ->where('work_unit_id', $asset->work_unit_id)
                    ->max('work_unit_sequence') + 1;
            }

            if (blank($asset->asset_code)) {
                $asset->asset_code = static::generateAssetCode($asset);
            }

            if ($asset->acquisition_price !== null && $asset->current_value === null) {
                $asset->current_value = $asset->acquisition_price;
            }

            if ($asset->acquisition_price !== null
                && $asset->useful_life_years
                && $asset->depreciation_per_year === null) {
                $asset->depreciation_per_year = round(
                    (float) $asset->acquisition_price / max(1, (int) $asset->useful_life_years),
                    2
                );
            }
        });

        static::updated(function ($model) {
            Cache::forget('sarpras_dashboard_version');
            $model->versionBump();
        });
    }

    /**
     * Kode aset otomatis: {KODE SATKER}-{URUT 4 DIGIT}.
     * Menjadi isi QR label per unit aset.
     */
    public static function generateAssetCode(Asset $asset): string
    {
        $workUnitCode = WorkUnit::query()->whereKey($asset->work_unit_id)->value('code') ?: 'AST';
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', $workUnitCode));
        $prefix = trim($prefix, '-') ?: 'AST';
        $sequence = max(1, (int) ($asset->work_unit_sequence ?: 1));

        do {
            $code = substr($prefix.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT), 0, 50);
            $exists = static::withTrashed()->where('asset_code', $code)->exists();
            $sequence++;
        } while ($exists);

        return $code;
    }

    protected function versionBump(): void
    {
        Cache::rememberForever('sarpras_dashboard_version', fn () => (int) Cache::get('sarpras_dashboard_version', 0) + 1);
    }

    protected $fillable = [
        'sync_token',
        'work_unit_id',
        'school_id',
        'asset_category_id',
        'room_id',
        'asset_code',
        'work_unit_sequence',
        'asset_name',
        'quantity',
        'unit',
        'brand',
        'model',
        'serial_number',
        'color',
        'specification',
        'acquisition_date',
        'acquisition_year',
        'acquisition_price',
        'acquisition_source',
        'funding_source',
        'supplier_name',
        'purchase_document_path',
        'condition',
        'status',
        'verification_status', 'verified_by', 'verified_at', 'verification_note',
        'is_bookable',
        'current_value',
        'depreciation_per_year',
        'useful_life_years',
        'last_valuation_date',
        'last_audit_date',
        'last_audit_by',
        'last_condition_update',
        'qr_generated_at',
        'photo_path',
        'notes',
        'is_active',
        'disposal_method',
        'disposal_date',
        'disposal_value',
        'disposal_reason',
        'created_by',
        'warranty_start_date',
        'warranty_end_date',
        'warranty_provider',
        'warranty_terms',
        'warranty_documents',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'acquisition_date' => 'date',
        'acquisition_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation_per_year' => 'decimal:2',
        'quantity' => 'integer',
        'work_unit_sequence' => 'integer',
        'useful_life_years' => 'integer',
        'last_valuation_date' => 'date',
        'last_audit_date' => 'date',
        'last_condition_update' => 'date',
        'qr_generated_at' => 'datetime',
        'is_bookable' => 'boolean',
        'is_active' => 'boolean',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'warranty_documents' => 'array',
    ];

    const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang', 'dihapus'];

    const STATUS_OPTIONS = ['active', 'borrowed', 'under_maintenance', 'under_repair', 'damaged', 'disposed', 'lost', 'tersedia', 'dipinjam', 'dalam_perbaikan', 'dihapus'];

    const ACQUISITION_SOURCE_OPTIONS = [
        'pembelian', 'hibah', 'sumbangan',
        'pengadaan_bos', 'bantuan_pemerintah', 'lainnya',
    ];

    // RELATIONSHIPS
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function room()
    {
        return $this->belongsTo(AssetRoom::class, 'room_id');
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastAuditBy()
    {
        return $this->belongsTo(User::class, 'last_audit_by');
    }

    // ASSET LIFECYCLE RELATIONSHIPS
    public function eventLogs()
    {
        return $this->hasMany(AssetEventLog::class, 'asset_id');
    }

    public function repairRequests()
    {
        return $this->hasMany(RepairRequest::class, 'asset_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'asset_id');
    }

    public function maintenanceHistories()
    {
        return $this->hasMany(MaintenanceHistory::class, 'asset_id');
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(AssetMaintenanceLog::class, 'asset_id');
    }

    public function photos()
    {
        return $this->hasMany(AssetPhoto::class, 'asset_id');
    }

    public function loans()
    {
        return $this->hasMany(AssetLoan::class, 'asset_id');
    }

    public function locationHistories()
    {
        return $this->hasMany(AssetLocationHistory::class, 'asset_id');
    }

    public function repairCostHistories()
    {
        return $this->hasMany(RepairCostHistory::class, 'asset_id');
    }

    public function qrScanHistories()
    {
        return $this->hasMany(QrScanHistory::class, 'asset_id');
    }

    public function movements()
    {
        return $this->hasMany(AssetMovement::class, 'asset_id');
    }

    public function healthMetric()
    {
        return $this->hasOne(AssetHealthMetric::class, 'asset_id');
    }

    public function activeMovement()
    {
        return $this->hasOne(AssetMovement::class, 'asset_id')
            ->whereIn('status', ['requested', 'approved', 'in_transit', 'received'])
            ->latestOfMany();
    }

    // ACCESSORS
    public function getKirConditionAttribute(): string
    {
        return match ($this->condition) {
            'rusak_berat' => 'RB',
            'rusak_ringan', 'rusak_sedang' => 'KB',
            'hilang', 'dihapus' => 'RB',
            default => 'B',
        };
    }

    public function getMasaPakaiAttribute(): int
    {
        return (int) ($this->useful_life_years ?: $this->category?->depreciation_years ?: 0);
    }

    public function getHargaPenyusutanAttribute(): float
    {
        if ($this->depreciation_per_year !== null) {
            return (float) $this->depreciation_per_year;
        }

        $years = $this->masa_pakai;

        return $years > 0 ? round((float) ($this->acquisition_price ?? 0) / $years, 2) : 0;
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTersedia($query)
    {
        return $query->where('status', 'tersedia');
    }

    public function scopeDipinjam($query)
    {
        return $query->where('status', 'dipinjam');
    }
}
