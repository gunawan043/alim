<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTracking extends Model
{
    use HasFactory;

    protected $table = 'delivery_tracking';

    protected $fillable = [
        'purchase_order_id',
        'tracking_number',
        'courier',
        'service_type',
        'dispatched_date',
        'estimated_arrival',
        'actual_arrival',
        'status',
        'current_location',
        'delivery_notes',
        'tracking_events',
        'recipient_user_id',
        'recipient_name',
        'received_at',
    ];

    protected $casts = [
        'dispatched_date' => 'date',
        'estimated_arrival' => 'date',
        'actual_arrival' => 'datetime',
        'received_at' => 'datetime',
        'tracking_events' => 'array',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETURNED = 'returned';

    public const ALLOWED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PICKED_UP,
        self::STATUS_IN_TRANSIT,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_FAILED,
        self::STATUS_RETURNED,
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_RETURNED,
        ], true);
    }

    public function addTrackingEvent(string $description, array $context = []): self
    {
        $events = $this->tracking_events ?? [];
        $events[] = [
            'at' => now()->toIso8601String(),
            'description' => $description,
            'context' => $context,
        ];
        $this->tracking_events = $events;

        return $this;
    }
}
