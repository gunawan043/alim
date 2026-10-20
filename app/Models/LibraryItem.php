<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryItem extends Model
{
    use HasUuids, SoftDeletes;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ON_LOAN = 'on_loan';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_LOST = 'lost';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat'];

    protected $table = 'library_items';

    protected $fillable = [
        'library_title_id', 'library_unit_id', 'inventory_code', 'shelf_location',
        'condition', 'status', 'acquisition_date', 'price', 'notes', 'qr_generated_at',
    ];

    protected $casts = ['acquisition_date' => 'date', 'price' => 'decimal:2', 'qr_generated_at' => 'datetime'];

    public function title(): BelongsTo { return $this->belongsTo(LibraryTitle::class, 'library_title_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function loanItems(): HasMany { return $this->hasMany(LibraryLoanItem::class); }
}
