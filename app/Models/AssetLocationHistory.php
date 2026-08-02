<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetLocationHistory extends Model
{
    use HasFactory;

    protected $table = 'asset_location_histories';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'asset_id',
        'from_room_id',
        'to_room_id',
        'moved_date',
        'reason',
        'moved_by',
    ];

    protected $casts = [
        'moved_date' => 'date',
    ];

    // RELATIONSHIPS
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromRoom()
    {
        return $this->belongsTo(AssetRoom::class, 'from_room_id');
    }

    public function toRoom()
    {
        return $this->belongsTo(AssetRoom::class, 'to_room_id');
    }

    public function mover()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
