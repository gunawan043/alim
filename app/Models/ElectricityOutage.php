<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ElectricityOutage extends Model
{
    protected $table = 'electricity_outages';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'occurred_at', 'location', 'work_unit_id', 'reporter_id', 'reporter_name',
        'outage_type', 'cause', 'restored_at', 'status', 'notes',
        'before_photo_path', 'before_photo_url', 'after_photo_path', 'after_photo_url',
        'created_by',
    ];

    protected $casts = ['occurred_at' => 'datetime', 'restored_at' => 'datetime'];

    public const TYPE_OPTIONS = ['terencana', 'tidak_terencana'];
    public const STATUS_OPTIONS = ['open', 'resolved'];

    public function actions(): HasMany { return $this->hasMany(ElectricityOutageAction::class, 'outage_id')->latest(); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reporter_id'); }
    public function workUnit(): BelongsTo { return $this->belongsTo(WorkUnit::class, 'work_unit_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
