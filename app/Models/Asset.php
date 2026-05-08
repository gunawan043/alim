<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assets';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id',
        'school_id',
        'asset_category_id',
        'room_id',
        'asset_code',
        'asset_name',
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
        'is_bookable',
        'current_value',
        'depreciation_per_year',
        'last_valuation_date',
        'last_audit_date',
        'last_audit_by',
        'last_condition_update',
        'qr_generated_at',
        'photo_path',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation_per_year' => 'decimal:2',
        'last_valuation_date' => 'date',
        'last_audit_date' => 'date',
        'last_condition_update' => 'date',
        'qr_generated_at' => 'datetime',
        'is_bookable' => 'boolean',
        'is_active' => 'boolean',
    ];

    const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang', 'dihapus'];

    const STATUS_OPTIONS = ['tersedia', 'dipinjam', 'dalam_perbaikan', 'dihapus'];

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
