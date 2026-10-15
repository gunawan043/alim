<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Template LJK berversi — geometri halaman + peta area jawaban (mm).
 *
 * Immutable: jangan mengubah `geometry` baris yang sudah dipakai lembar
 * tercetak. Buat versi baru via LjkTemplateService::createVersion().
 */
class LjkTemplate extends Model
{
    use HasFactory;

    protected $table = 'ljk_templates';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code', 'version', 'name', 'paper', 'orientation', 'columns',
        'box_width_mm', 'box_height_mm', 'row_height_mm',
        'geometry', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'columns' => 'integer',
        'box_width_mm' => 'decimal:2',
        'box_height_mm' => 'decimal:2',
        'row_height_mm' => 'decimal:2',
        'geometry' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(LjkSheet::class, 'template_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(LjkExam::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Jumlah halaman yang direncanakan template.
     */
    public function pageCount(): int
    {
        return max(1, (int) ($this->geometry['pages'] ?? 1));
    }
}
