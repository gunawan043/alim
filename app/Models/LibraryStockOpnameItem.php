<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryStockOpnameItem extends Model
{
    use HasUuids;

    protected $table = 'library_stock_opname_items';

    protected $fillable = ['library_stock_opname_id', 'library_item_id', 'finding', 'system_status', 'physical_location', 'condition', 'notes', 'checked_by', 'checked_at'];
    protected $casts = ['checked_at' => 'datetime'];

    public function session(): BelongsTo { return $this->belongsTo(LibraryStockOpname::class, 'library_stock_opname_id'); }
    public function item(): BelongsTo { return $this->belongsTo(LibraryItem::class, 'library_item_id'); }
    public function checker(): BelongsTo { return $this->belongsTo(User::class, 'checked_by'); }
}
