<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupervisiTimAnggota extends Model
{
    protected $table = 'supervisi_tim_anggota';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'supervisi_sk_id',
        'gtk_profile_id',
        'peran',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
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

    public function sk(): BelongsTo
    {
        return $this->belongsTo(SupervisiSk::class, 'supervisi_sk_id');
    }

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(GtkProfile::class, 'gtk_profile_id');
    }
}
