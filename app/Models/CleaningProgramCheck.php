<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CleaningProgramCheck extends Model
{
    protected $table = 'cleaning_program_checks';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = ['program_id', 'year', 'month', 'is_done', 'checked_by', 'checked_at', 'notes'];
    protected $casts = ['is_done' => 'boolean', 'checked_at' => 'datetime', 'year' => 'integer', 'month' => 'integer'];

    public function program(): BelongsTo { return $this->belongsTo(CleaningProgram::class, 'program_id'); }
    public function checker(): BelongsTo { return $this->belongsTo(User::class, 'checked_by'); }
}
