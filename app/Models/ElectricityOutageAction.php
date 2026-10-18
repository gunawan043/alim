<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ElectricityOutageAction extends Model
{
    protected $table = 'electricity_outage_actions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = ['outage_id', 'action', 'officer_id', 'notes'];

    public function outage(): BelongsTo { return $this->belongsTo(ElectricityOutage::class, 'outage_id'); }
    public function officer(): BelongsTo { return $this->belongsTo(User::class, 'officer_id'); }
}
