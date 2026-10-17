<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VendorCommunication extends Model
{
    use HasFactory;

    protected $table = 'vendor_communications';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'subject',
        'message',
        'direction',
        'channel',
        'entity_type',
        'entity_id',
        'sender_id',
        'sender_type',
        'sender_name',
        'recipient_id',
        'recipient_name',
        'attachments',
        'created_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'created_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
