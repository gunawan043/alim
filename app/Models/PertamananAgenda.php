<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PertamananAgenda extends Model
{
    protected $table = 'pertamanan_agendas';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = ['agenda_date', 'academic_year', 'officer_id', 'notes', 'created_by'];

    protected $casts = ['agenda_date' => 'date'];

    public function items(): HasMany
    {
        return $this->hasMany(PertamananAgendaItem::class, 'agenda_id')->orderBy('sort_order');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public const SECTIONS = [
        'pemeliharaan' => 'A. Agenda Pemeliharaan',
        'pembibitan' => 'B. Agenda Pembibitan Tanaman',
        'permintaan_darurat' => 'C. Permintaan Darurat',
    ];
}
