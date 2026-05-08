<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApprovalFlow extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    // RELATIONSHIPS
    public function steps()
    {
        return $this->hasMany(ApprovalFlowStep::class)->orderBy('step_order');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ACCESSORS
    public function getStepCountAttribute()
    {
        return $this->steps()->count();
    }
}