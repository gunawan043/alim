<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkOrderProgressNote extends Model
{
    use HasFactory;

    protected $table = 'work_order_progress_notes';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'id', 'work_order_id', 'user_id', 'note', 'note_type', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}