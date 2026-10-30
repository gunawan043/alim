<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PekanEfektifPrintSetting extends Model
{
    use HasUuids;

    protected $table = 'pekan_efektif_print_settings';

    protected $fillable = [
        'school_id', 'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
        'font_size', 'title_font_size', 'table_font_size', 'info_font_size',
        'footer_font_size', 'line_height', 'paper_size', 'show_kop',
        'show_signature', 'city', 'guru_nama', 'guru_nip', 'updated_by',
    ];

    protected $casts = [
        'margin_top' => 'float',
        'margin_right' => 'float',
        'margin_bottom' => 'float',
        'margin_left' => 'float',
        'font_size' => 'float',
        'title_font_size' => 'float',
        'table_font_size' => 'float',
        'info_font_size' => 'float',
        'footer_font_size' => 'float',
        'line_height' => 'float',
        'show_kop' => 'boolean',
        'show_signature' => 'boolean',
    ];

    public static function defaultLayout(): array
    {
        return [
            'margin_top' => 10.0,
            'margin_right' => 10.0,
            'margin_bottom' => 12.0,
            'margin_left' => 12.0,
            'font_size' => 10.0,
            'title_font_size' => 14.0,
            'table_font_size' => 9.0,
            'info_font_size' => 10.0,
            'footer_font_size' => 8.0,
            'line_height' => 1.2,
            'paper_size' => 'a4',
            'show_kop' => true,
            'show_signature' => true,
            'city' => null,
            'guru_nama' => null,
            'guru_nip' => null,
        ];
    }

    public static function forSchool(string $schoolId): ?self
    {
        return static::where('school_id', $schoolId)->first();
    }

    public function layoutSettings(): array
    {
        $defaults = static::defaultLayout();
        $paper = (string) ($this->paper_size ?: $defaults['paper_size']);

        return array_merge($defaults, [
            'margin_top' => (float) ($this->margin_top ?? $defaults['margin_top']),
            'margin_right' => (float) ($this->margin_right ?? $defaults['margin_right']),
            'margin_bottom' => (float) ($this->margin_bottom ?? $defaults['margin_bottom']),
            'margin_left' => (float) ($this->margin_left ?? $defaults['margin_left']),
            'font_size' => (float) ($this->font_size ?? $defaults['font_size']),
            'title_font_size' => (float) ($this->title_font_size ?? $defaults['title_font_size']),
            'table_font_size' => (float) ($this->table_font_size ?? $defaults['table_font_size']),
            'info_font_size' => (float) ($this->info_font_size ?? $defaults['info_font_size']),
            'footer_font_size' => (float) ($this->footer_font_size ?? $defaults['footer_font_size']),
            'line_height' => (float) ($this->line_height ?? $defaults['line_height']),
            'paper_size' => in_array($paper, ['a4', 'folio', 'letter'], true) ? $paper : 'a4',
            'show_kop' => (bool) ($this->show_kop ?? $defaults['show_kop']),
            'show_signature' => (bool) ($this->show_signature ?? $defaults['show_signature']),
            'city' => $this->city,
            'guru_nama' => $this->guru_nama,
            'guru_nip' => $this->guru_nip,
        ]);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
