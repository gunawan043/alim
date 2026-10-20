<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryStockOpname extends Model
{
    use HasUuids;

    protected $table = 'library_stock_opnames';

    protected $fillable = ['library_unit_id', 'academic_year_id', 'session_date', 'status', 'coordinator_id', 'notes', 'started_at', 'completed_at'];
    protected $casts = ['session_date' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function coordinator(): BelongsTo { return $this->belongsTo(User::class, 'coordinator_id'); }
    public function items(): HasMany { return $this->hasMany(LibraryStockOpnameItem::class, 'library_stock_opname_id'); }
}
