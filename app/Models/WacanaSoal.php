<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Wacana / soal cerita — bacaan bersama untuk beberapa butir soal.
 */
class WacanaSoal extends Model
{
    use HasFactory, LogsDeletion, SoftDeletes;

    protected $table = 'wacana_soal';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'bank_soal_id', 'school_id', 'judul', 'teks', 'sumber', 'rentang_nomor', 'created_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'wacana_id');
    }

    public function hasArabic(): bool
    {
        return \App\Services\Ljk\LjkArabicText::hasArabic($this->teks) || \App\Services\Ljk\LjkArabicText::hasArabic($this->judul);
    }
}
