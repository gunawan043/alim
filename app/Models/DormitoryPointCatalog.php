<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DormitoryPointCatalog extends Model
{
    protected $table = 'dormitory_point_catalogs';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['kind','code','name','category','severity','points_min','points_max','is_permanent','is_active','sort_order','description'];
    protected $casts = ['is_permanent'=>'boolean','is_active'=>'boolean','points_min'=>'integer','points_max'=>'integer','sort_order'=>'integer'];
    protected static function booted(): void { static::creating(fn (self $m) => $m->id = $m->id ?: (string) Str::uuid()); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeKind($q, string $kind) { return $q->where('kind', $kind); }

    public function violations()
    {
        return $this->hasMany(DormitoryViolation::class, 'point_catalog_id');
    }

    public function rewards()
    {
        return $this->hasMany(DormitoryReward::class, 'point_catalog_id');
    }

    /**
     * Map severity (with points fallback) to the legacy violation_category enum.
     */
    public function severityCategory(): string
    {
        $severity = strtolower((string) $this->severity);

        if (str_contains($severity, 'ringan')) {
            return 'ringan';
        }

        if (str_contains($severity, 'sedang')) {
            return 'sedang';
        }

        if (str_contains($severity, 'berat')) {
            return 'berat';
        }

        $points = (int) $this->points_min;

        return $points <= 14 ? 'ringan' : ($points <= 49 ? 'sedang' : 'berat');
    }

    /**
     * Map catalog category/sub-category to DormitoryReward category keys.
     */
    public function rewardCategoryValue(): string
    {
        $category = strtolower((string) $this->category);
        $sub = strtolower((string) $this->severity);

        if ($sub === 'tahfidz') {
            return DormitoryReward::CAT_TAHFIDZ;
        }

        if (array_key_exists($category, DormitoryReward::categories())) {
            return $category;
        }

        if ($category === 'sikap' || in_array($sub, ['interpersonal', 'sosial'], true)) {
            return DormitoryReward::CAT_AKHLAK;
        }

        return DormitoryReward::CAT_PRESTASI;
    }

    public function rewardLevelValue(): string
    {
        $points = (int) $this->points_min;

        if ($points >= 50) {
            return DormitoryReward::LEVEL_IMBITASA;
        }

        if ($points >= 20) {
            return DormitoryReward::LEVEL_UGGALAN;
        }

        return DormitoryReward::LEVEL_BIASA;
    }
}
