<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SoalCloneLog extends Model
{
    use HasFactory;

    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $table = 'soal_clone_log';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'soal_asli_id',
        'soal_clone_id',
        'cloned_by',
        'cloned_at',
        'from_school_id',
        'to_school_id',
        'clone_type',
        'notes',
    ];

    protected $casts = [
        'cloned_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function soalAsli(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_asli_id');
    }

    public function soalClone(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_clone_id');
    }

    public function cloner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloned_by');
    }
}
