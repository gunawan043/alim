<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Divisi extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'divisis';

    protected $fillable = ['nama', 'kode', 'deskripsi', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // RELATIONSHIPS
    public function dokumenIso(): HasMany
    {
        return $this->hasMany(DokumenIso::class, 'divisi_id');
    }

    public function dokumenIsoCount(): int
    {
        return $this->dokumenIso()->count();
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // AUDIT LOGGING
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
