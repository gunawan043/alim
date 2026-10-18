<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PertamananAgendaItem extends Model
{
    protected $table = 'pertamanan_agenda_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'agenda_id', 'section', 'group_title', 'title', 'status',
        'officer_id', 'notes', 'sort_order', 'created_by',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public const STATUS_OPTIONS = [
        'belum' => 'Belum',
        'selesai' => 'Selesai',
        'tunda' => 'Tunda',
    ];

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(PertamananAgenda::class, 'agenda_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
