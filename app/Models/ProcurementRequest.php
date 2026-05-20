<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProcurementRequest extends Model
{
    use HasFactory;

    protected $table = 'procurement_requests';
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
        'request_number',
        'request_date',
        'requested_by',
        'purpose',
        'urgency',
        'budget_source',
        'total_estimated_budget',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'procurement_method',
        'vendor_name',
        'purchase_order_number',
        'purchase_order_date',
        'delivery_date',
        'received_by',
        'received_date',
        'total_actual_cost',
        'payment_status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'purchase_order_date' => 'date',
        'delivery_date' => 'date',
        'received_date' => 'date',
        'total_estimated_budget' => 'decimal:2',
        'total_actual_cost' => 'decimal:2',
    ];

    const STATUS_OPTIONS = [
        'draft', 'pending', 'approved', 'rejected',
        'ordered', 'delivered', 'completed', 'cancelled',
    ];

    const URGENCY_OPTIONS = ['rendah', 'normal', 'tinggi', 'mendesak'];

    const PAYMENT_STATUS_OPTIONS = ['belum_dibayar', 'dibayar_sebagian', 'lunas'];

    // RELATIONSHIPS
    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ProcurementRequestItem::class, 'procurement_request_id');
    }

    // SCOPES
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
