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
        'context_type',
        'context_id',
        'photo_path',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'caption',
        'photo_type',
        'taken_at',
        'uploaded_by',
        'metadata',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    // RELATIONSHIPS
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function context()
    {
        return $this->morphTo(__FUNCTION__, 'context_type', 'context_id');
    }

    // SCOPES
    public function scopeOfType($query, string $type)
    {
        return $query->where('photo_type', $type);
    }

    public function scopeByContext($query, string $contextType, ?string $contextId = null)
    {
        $q = $query->where('context_type', $contextType);
        if ($contextId) {
            $q->where('context_id', $contextId);
        }
        return $q;
    }
}
