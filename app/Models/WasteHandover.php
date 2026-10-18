<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WasteHandover extends Model
{
    protected $table = 'waste_handovers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'handover_date', 'destination', 'waste_type', 'total_weight_kg', 'total_sacks',
        'receipt_number', 'receipt_path', 'receipt_url', 'photo_path', 'photo_url',
        'officer_id', 'notes', 'created_by',
    ];

    protected $casts = ['handover_date' => 'date', 'total_weight_kg' => 'decimal:2', 'total_sacks' => 'integer'];

    public function officer(): BelongsTo { return $this->belongsTo(User::class, 'officer_id'); }
}
