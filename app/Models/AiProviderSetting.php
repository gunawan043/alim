<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Provider AI (OpenAI-compatible) — berbayar maupun gratis/lokal.
 * API key disimpan terenkripsi menggunakan cast `encrypted`.
 */
class AiProviderSetting extends Model
{
    protected $table = 'ai_provider_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'name',
        'provider',
        'endpoint',
        'api_key',
        'model',
        'extra',
        'is_default',
        'is_active',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'created_by',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'extra' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function hasApiKey(): bool
    {
        return filled($this->api_key);
    }

    /**
     * Jadikan provider ini default (hanya satu default aktif).
     */
    public function makeDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true, 'is_active' => true])->save();
    }
}
