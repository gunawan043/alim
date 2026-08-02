<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DormitoryInventory extends Model
{
    use SoftDeletes;

    protected $table = 'dormitory_inventories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'room_id',
        'dormitory_id',
        'item_name',
        'item_code',
        'quantity',
        'condition',
        'last_checked_at',
        'checked_by',
        'notes',
        'asset_id',   // baru: FK ke assets
        'category_id', // baru: FK ke asset_categories
    ];

    protected $casts = [
        'quantity' => 'integer',
        'last_checked_at' => 'datetime',
    ];

    protected $dates = ['last_checked_at', 'deleted_at'];

    /**
     * Aliases so views can use the conventional $item->name / $item->code
     * while the underlying columns are item_name / item_code.
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['item_name'] ?? null;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['item_code'] ?? null;
    }

    // ------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    // Relation ke aset sarpas (opsional)
    public function asset(): ?BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }

    // Relation ke kategori
    public function category(): ?BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id', 'id');
    }
}
