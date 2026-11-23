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
                'show_school' => true,
                'show_photo' => true,
                'show_name' => true,
                'show_nis' => true,
                'show_nisn' => true,
                'show_class' => true,
                'show_validity' => true,
                'font_size' => 8.0,
                'title_size' => 9.0,
                'line_height' => 1.3,
                'photo_width' => 20.0,
                'photo_height' => 25.0,
            ],
            'back' => [
                'show_note' => true,
                'show_signature' => true,
                'show_signature_name' => true,
                'show_signature_title' => true,
                'font_size' => 7.5,
                'line_height' => 1.35,
                'signature_width' => 22.0,
                'signature_height' => 12.0,
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
