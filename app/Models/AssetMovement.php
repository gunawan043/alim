<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AssetMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asset_movements';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = $model->id ?? (string) Str::uuid();
            $model->movement_number = $model->generateMovementNumber();
        });
    }

    protected $fillable = [
        'id', 'sync_token', 'movement_number', 'asset_id', 'work_unit_id',
        'from_room_id', 'to_room_id', 'from_holder_id', 'to_holder_id',
        'reason', 'justification', 'status', 'requester_id',
        'approver_id', 'carrier_id', 'receiver_id', 'verifier_id',
        'approved_at', 'in_transit_at', 'received_at', 'verified_at',
        'completed_at', 'condition_snapshot', 'condition_after',
        'verification_notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'received_at' => 'datetime',
        'verified_at' => 'datetime',
        'completed_at' => 'datetime',
        'condition_snapshot' => 'array',
        'condition_after' => 'array',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function carrier()
    {
        return $this->belongsTo(User::class, 'carrier_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class, 'work_unit_id');
    }

    public function fromRoom()
    {
        return $this->belongsTo(AssetRoom::class, 'from_room_id');
    }

    public function toRoom()
    {
        return $this->belongsTo(AssetRoom::class, 'to_room_id');
    }

    public function holder()
    {
        return $this->belongsTo(User::class, 'to_holder_id');
    }

    const STATUSES = [
        'requested', 'approved', 'rejected', 'in_transit',
        'received', 'verified', 'completed', 'cancelled',
    ];

    const VALID_TRANSITIONS = [
        'requested' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['in_transit'],
        'rejected' => ['requested'],
        'in_transit' => ['received'],
        'received' => ['verified', 'rejected'],
        'verified' => ['completed', 'received'],
        'completed' => [],
        'cancelled' => ['requested'],
    ];

    public function isValidNextStatus(string $next): bool
    {
        return in_array($next, self::VALID_TRANSITIONS[$this->status] ?? []);
    }

    protected function generateMovementNumber(): string
    {
        $year = now()->year;
        $num = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('MOV-%d-%05d', $year, $num);
    }
}
