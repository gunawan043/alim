<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentMutationSetting extends Model
{
    protected $table = 'student_mutation_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
        'font_family', 'font_size', 'line_height', 'paper_size', 'show_kop', 'updated_by',
    ];

    protected $casts = [
        'margin_top' => 'float',
        'margin_right' => 'float',
        'margin_bottom' => 'float',
        'margin_left' => 'float',
        'font_size' => 'float',
        'line_height' => 'float',
        'show_kop' => 'boolean',
    ];

    public static function defaultLayout(): array
    {
        return [
            'margin_top' => 20.0,
            'margin_right' => 25.0,
            'margin_bottom' => 15.0,
            'margin_left' => 25.0,
            'font_family' => 'Times New Roman',
            'font_size' => 12.0,
            'line_height' => 1.20,
            'paper_size' => 'a4',
            'show_kop' => true,
        ];
    }

    public static function forSchool(?string $schoolId): self
    {
        if (! $schoolId) {
            return new self(self::defaultLayout());
        }

        return self::firstOrCreate(
            ['school_id' => $schoolId],
            self::defaultLayout()
        );
    }

    public function layoutSettings(): array
    {
        $defaults = self::defaultLayout();
        $paper = in_array($this->paper_size, ['a4', 'folio', 'letter'], true) ? $this->paper_size : 'a4';
        $font = in_array($this->font_family, ['Times New Roman', 'Arial', 'Georgia', 'Bookman Old Style', 'Tahoma'], true)
            ? $this->font_family : 'Times New Roman';

        return [
            'margin_top' => (float) ($this->margin_top ?? $defaults['margin_top']),
            'margin_right' => (float) ($this->margin_right ?? $defaults['margin_right']),
            'margin_bottom' => (float) ($this->margin_bottom ?? $defaults['margin_bottom']),
            'margin_left' => (float) ($this->margin_left ?? $defaults['margin_left']),
            'font_family' => $font,
            'font_size' => (float) ($this->font_size ?? $defaults['font_size']),
            'line_height' => (float) ($this->line_height ?? $defaults['line_height']),
            'paper_size' => $paper,
            'show_kop' => (bool) ($this->show_kop ?? $defaults['show_kop']),
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
