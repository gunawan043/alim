<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChecklistInstanceResponse extends Model
{
    use HasFactory;

    protected $table = 'checklist_instance_responses';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'id', 'instance_id', 'template_item_id', 'response_value',
        'passed', 'notes',
    ];

    protected $casts = [
        'response_value' => 'array',
        'passed' => 'boolean',
    ];

    public function instance()
    {
        return $this->belongsTo(ChecklistInstance::class, 'instance_id');
    }

    public function item()
    {
        return $this->belongsTo(ChecklistTemplateItem::class, 'template_item_id');
    }
}