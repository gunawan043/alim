<?php

namespace App\Models\Concerns;

trait HasPrintLayout
{
    public static function defaultLayout(): array
    {
        return [
            'margin_top' => 10.0,
            'margin_right' => 12.0,
            'margin_bottom' => 14.0,
            'margin_left' => 15.0,
            'font_size' => 11.0,
            'title_font_size' => 13.0,
            'table_font_size' => 10.0,
            'line_height' => 1.25,
            'paper_size' => 'a4',
            'show_kop' => true,
        ];
    }

    public function layoutSettings(): array
    {
        $defaults = static::defaultLayout();
        $saved = is_array($this->layout_settings ?? null) ? $this->layout_settings : [];

        return [
            'margin_top' => (float) ($saved['margin_top'] ?? $defaults['margin_top']),
            'margin_right' => (float) ($saved['margin_right'] ?? $defaults['margin_right']),
            'margin_bottom' => (float) ($saved['margin_bottom'] ?? $defaults['margin_bottom']),
            'margin_left' => (float) ($saved['margin_left'] ?? $defaults['margin_left']),
            'font_size' => (float) ($saved['font_size'] ?? $defaults['font_size']),
            'title_font_size' => (float) ($saved['title_font_size'] ?? $defaults['title_font_size']),
            'table_font_size' => (float) ($saved['table_font_size'] ?? $defaults['table_font_size']),
            'line_height' => (float) ($saved['line_height'] ?? $defaults['line_height']),
            'paper_size' => in_array(($saved['paper_size'] ?? $defaults['paper_size']), ['a4', 'folio', 'letter'], true)
                ? ($saved['paper_size'] ?? $defaults['paper_size'])
                : 'a4',
            'show_kop' => (bool) ($saved['show_kop'] ?? $defaults['show_kop']),
        ];
    }
}
