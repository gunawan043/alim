<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetPhoto extends Model
{
    use HasFactory;

    protected $table = 'asset_photos';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'asset_id',
        'photo_path',
        'caption',
        'uploaded_by',
    ];

    // RELATIONSHIPS
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
