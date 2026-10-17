<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SoalOption extends Model
{
    use HasFactory;

    protected $table = 'soal_options';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'soal_id',
        'label',
        'teks_opsi',
        'gambar_path',
        'is_correct',
        'urutan',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'urutan' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
