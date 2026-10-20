<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryMemberCard extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_LOST = 'lost';
    public const STATUS_REVOKED = 'revoked';

    protected $table = 'library_member_cards';

    protected $fillable = [
        'library_member_id', 'card_number', 'status', 'issued_at', 'valid_until',
        'replacement_of_id', 'replacement_fee', 'fee_status', 'printed_at',
        'print_count', 'revoked_at', 'revoked_by',
    ];

    protected $casts = [
        'issued_at' => 'date', 'valid_until' => 'date', 'revoked_at' => 'datetime',
        'printed_at' => 'datetime', 'replacement_fee' => 'decimal:2', 'print_count' => 'integer',
    ];

    public function member(): BelongsTo { return $this->belongsTo(LibraryMember::class, 'library_member_id'); }
    public function replacedCard(): BelongsTo { return $this->belongsTo(self::class, 'replacement_of_id'); }
}
