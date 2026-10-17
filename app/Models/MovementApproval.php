<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MovementApproval extends Model
{
    use HasFactory;

    protected $table = 'movement_approvals';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'id', 'movement_id', 'user_id', 'action', 'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function movement()
    {
        return $this->belongsTo(AssetMovement::class, 'movement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
