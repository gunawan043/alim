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
                'school' => ['x' => 4, 'y' => 2, 'w' => 77.6, 'h' => 5.5, 'size' => 8.5, 'align' => 'center', 'bold' => true],
                'photo' => ['x' => 4, 'y' => 9, 'w' => 20, 'h' => 25],
                'name' => ['x' => 27, 'y' => 9, 'w' => 54.6, 'h' => 8, 'size' => 9.5, 'align' => 'left', 'bold' => true],
                'nis' => ['x' => 27, 'y' => 19, 'w' => 54.6, 'h' => 5, 'size' => 7.5, 'align' => 'left', 'bold' => false],
                'nisn' => ['x' => 27, 'y' => 25, 'w' => 54.6, 'h' => 5, 'size' => 7.5, 'align' => 'left', 'bold' => false],
                'class' => ['x' => 27, 'y' => 31, 'w' => 54.6, 'h' => 5, 'size' => 7.5, 'align' => 'left', 'bold' => false],
                'validity' => ['x' => 27, 'y' => 39, 'w' => 54.6, 'h' => 5, 'size' => 6.5, 'align' => 'left', 'bold' => false],
            ],
            'back' => [
                'note' => ['x' => 5, 'y' => 5, 'w' => 75.6, 'h' => 10, 'size' => 7, 'align' => 'left', 'bold' => false],
                'signature' => ['x' => 54, 'y' => 24, 'w' => 22, 'h' => 12],
                'signature_name' => ['x' => 40, 'y' => 37, 'w' => 41.6, 'h' => 5, 'size' => 7, 'align' => 'center', 'bold' => true],
                'signature_title' => ['x' => 40, 'y' => 42, 'w' => 41.6, 'h' => 5, 'size' => 6.5, 'align' => 'center', 'bold' => false],
            ],
        ];
    }

    public function resolvedLayout(): array
    {
        $default = self::defaultLayout();
        $saved = is_array($this->layout) ? $this->layout : [];

        foreach (['front', 'back'] as $side) {
            foreach ($default[$side] as $field => $values) {
                $default[$side][$field] = array_merge($values, $saved[$side][$field] ?? []);
            }
        }

        return $default;
    }
}
