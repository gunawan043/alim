<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $table = 'checklist_templates';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'id', 'code', 'name', 'workflow_type', 'category_slug',
        'is_active', 'version', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(ChecklistTemplateItem::class, 'template_id')->orderBy('sequence');
    }

    public function instances()
    {
        return $this->hasMany(ChecklistInstance::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}