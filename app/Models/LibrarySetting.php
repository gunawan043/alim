<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibrarySetting extends Model
{
    use HasUuids;

    protected $table = 'library_settings';

    protected $fillable = [
        'library_unit_id', 'academic_year_id', 'loan_days', 'max_active_loans',
        'allow_renewal', 'max_renewals', 'renewal_days', 'allow_fine',
        'fine_per_day', 'max_fine', 'replacement_card_fee',
    ];

    protected $casts = [
        'allow_renewal' => 'boolean', 'allow_fine' => 'boolean',
        'loan_days' => 'integer', 'max_active_loans' => 'integer',
        'max_renewals' => 'integer', 'renewal_days' => 'integer',
        'fine_per_day' => 'decimal:2', 'max_fine' => 'decimal:2',
        'replacement_card_fee' => 'decimal:2',
    ];

    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }

    public static function forUnitYear(string $unitId, ?string $yearId): self
    {
        return static::firstOrCreate(
            ['library_unit_id' => $unitId, 'academic_year_id' => $yearId],
            ['loan_days' => 7, 'max_active_loans' => 3]
        );
    }
}
