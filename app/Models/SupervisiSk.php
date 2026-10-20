<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupervisiSk extends Model
{
    protected $table = 'supervisi_sk';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'semester',
        'nomor',
        'tanggal',
        'menimbang',
        'mengingat',
    ];

    protected $casts = [
        'semester' => 'integer',
        'tanggal' => 'date',
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

    public function anggota(): HasMany
    {
        return $this->hasMany(SupervisiTimAnggota::class, 'supervisi_sk_id')->orderBy('urutan');
    }
}
