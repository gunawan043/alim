<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Catatan konsumsi makan asrama per tanggal dan jenis makan.
 */
class DormitoryMealConsumption extends Model
{
    protected $table = 'dormitory_meal_consumptions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id', 'academic_year_id', 'consumption_date', 'meal_type',
        'students_count', 'guests_count', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'students_count' => 'integer',
        'guests_count' => 'integer',
    ];

    public const MEAL_TYPES = [
        'sarapan' => 'Sarapan',
        'makan_siang' => 'Makan Siang',
        'makan_malam' => 'Makan Malam',
    ];

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function mealLabel(): string
    {
        return self::MEAL_TYPES[$this->meal_type] ?? $this->meal_type;
    }

    public function totalPortions(): int
    {
        return (int) $this->students_count + (int) $this->guests_count;
    }
}
