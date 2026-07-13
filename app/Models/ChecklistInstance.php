<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChecklistInstance extends Model
{
    use HasFactory;

    protected $table = 'checklist_instances';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'id', 'template_id', 'context_type', 'context_id',
        'executor_id', 'status', 'failed_items_count',
        'total_items_count', 'result_summary',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'result_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function responses()
    {
        return $this->hasMany(ChecklistInstanceResponse::class, 'instance_id');
    }

    public function context()
    {
        return $this->morphTo();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}