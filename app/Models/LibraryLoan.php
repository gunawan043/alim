<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryLoan extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_OVERDUE = 'overdue';

    protected $table = 'library_loans';

    protected $fillable = [
        'library_unit_id', 'library_member_id', 'loan_number', 'loan_date', 'due_date',
        'returned_at', 'status', 'physical_card_verified', 'card_verified_at',
        'processed_by', 'notes',
    ];

    protected $casts = [
        'loan_date' => 'date', 'due_date' => 'date', 'returned_at' => 'date',
        'physical_card_verified' => 'boolean', 'card_verified_at' => 'datetime',
    ];

    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function member(): BelongsTo { return $this->belongsTo(LibraryMember::class, 'library_member_id'); }
    public function items(): HasMany { return $this->hasMany(LibraryLoanItem::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
