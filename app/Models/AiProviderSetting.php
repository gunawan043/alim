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

        // Hanya satu provider yang boleh aktif. Mengaktifkan provider
        // otomatis menonaktifkan provider lain dan menjadikannya default.
        static::saving(function (self $provider) {
            if ($provider->is_active) {
                $provider->is_default = true;

                $others = static::query();
                if ($provider->exists) {
                    $others->whereKeyNot($provider->getKey());
                }

                $others->update(['is_active' => false, 'is_default' => false]);

                return;
            }

            if ($provider->is_default) {
                $provider->is_default = false;
            }
        });
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

    /**
     * Grup berbagi API key (mis. semua model OpenRouter memakai satu key).
     */
    public function keyGroup(): ?string
    {
        $extra = is_array($this->extra) ? $this->extra : [];
        $group = $extra['key_group'] ?? null;

        return filled($group) ? (string) $group : null;
    }

    /**
     * API key efektif: key sendiri, atau key dari provider lain dalam grup
     * yang sama. Key tidak pernah dikirim ke browser.
     */
    public function effectiveApiKey(): ?string
    {
        if (filled($this->api_key)) {
            return (string) $this->api_key;
        }

        $group = $this->keyGroup();
        if (! $group) {
            return null;
        }

        $source = static::query()
            ->whereKeyNot($this->getKey())
            ->get(['id', 'api_key', 'is_active', 'is_default', 'extra'])
            ->filter(fn (self $provider) => filled($provider->api_key) && $provider->keyGroup() === $group)
            ->sortByDesc(fn (self $provider) => ($provider->is_default ? 2 : 0) + ($provider->is_active ? 1 : 0))
            ->first();

        return $source?->api_key;
    }

    public function hasApiKey(): bool
    {
        return filled($this->effectiveApiKey());
    }

    /**
     * Jadikan provider ini aktif dan default. Hanya satu provider aktif;
     * provider lain otomatis dinonaktifkan.
     */
    public function makeDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true, 'is_active' => true])->save();
    }
}
