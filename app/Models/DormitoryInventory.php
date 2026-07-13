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
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
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
    ];

    protected $casts = [
        'quantity' => 'integer',
        'last_checked_at' => 'datetime',
    ];

    protected $dates = ['last_checked_at', 'deleted_at'];

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
}
