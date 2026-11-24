<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentCardSetting extends Model
{
    protected $table = 'student_card_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'front_background_path',
        'back_background_path',
        'signature_path',
        'signature_name',
        'signature_title',
        'validity_text',
        'card_width_mm',
        'card_height_mm',
        'layout',
    ];

    protected $casts = [
        'card_width_mm' => 'float',
        'card_height_mm' => 'float',
        'layout' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public static function defaultLayout(): array
    {
        return [
            'front' => [
                'show_photo' => true,
                'show_name' => true,
                'show_nis' => true,
                'show_nisn' => true,
                'show_ttl' => true,
                'show_address' => true,
                'show_class' => true,
                'show_signature' => true,
                'show_signature_name' => true,
                'show_signature_title' => true,
                'font_size' => 7.8,
                'line_height' => 1.25,
                'info_left' => 27.0,
                'info_top' => 8.0,
                'photo_width' => 20.0,
                'photo_height' => 25.0,
                'photo_left' => 4.0,
                'photo_top' => 10.0,
                'qr_size' => 14.0,
                'signature_width' => 22.0,
                'signature_height' => 12.0,
                'signature_title_font_size' => 7.0,
                'signature_name_font_size' => 7.0,
                'signature_gap' => 0.5,
            ],
            'back' => [
                'show_note' => true,
                'show_back_qr' => true,
                'back_qr_source' => 'nis',
                'back_qr_left' => 60.0,
                'back_qr_top' => 24.0,
                'back_qr_size' => 22.0,
                'font_size' => 7.5,
                'line_height' => 1.35,
            ],
        ];
    }

    public function resolvedLayout(): array
    {
        $default = self::defaultLayout();
        $saved = is_array($this->layout) ? $this->layout : [];

        foreach (['front', 'back'] as $side) {
            foreach ($default[$side] as $key => $value) {
                if (array_key_exists($key, $saved[$side] ?? [])) {
                    $default[$side][$key] = $saved[$side][$key];
                }
            }
        }

        return $default;
    }
}
