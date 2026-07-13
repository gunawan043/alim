<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChecklistTemplateItem extends Model
{
    use HasFactory;

    protected $table = 'checklist_template_items';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'id', 'template_id', 'sequence', 'label', 'description',
        'response_type', 'options', 'is_required', 'triggers_failure',
        'failure_severity',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'options' => 'array',
        'is_required' => 'boolean',
        'triggers_failure' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function responses()
    {
        return $this->hasMany(ChecklistInstanceResponse::class, 'template_item_id');
    }
}