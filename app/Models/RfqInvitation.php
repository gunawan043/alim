<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqInvitation extends Model
{
    use HasFactory;

    protected $table = 'rfq_invitations';

    protected $fillable = [
        'rfq_id',
        'vendor_id',
        'status',
        'viewed_at',
        'responded_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public const STATUS_INVITED = 'invited';

    public const STATUS_VIEWED = 'viewed';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_SUBMITTED = 'submitted';

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function markViewed(): self
    {
        if ($this->status === self::STATUS_INVITED) {
            $this->status = self::STATUS_VIEWED;
            $this->viewed_at = now();
            $this->save();
        }

        return $this;
    }
}
