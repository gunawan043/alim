<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupervisiPengaturan extends Model
{
    protected $table = 'supervisi_pengaturan';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'semester',
        'supervisor_gtk_id',
        'supervisee_gtk_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'semester' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(GtkProfile::class, 'supervisor_gtk_id');
    }

    public function supervisee(): BelongsTo
    {
        return $this->belongsTo(GtkProfile::class, 'supervisee_gtk_id');
    }
}
