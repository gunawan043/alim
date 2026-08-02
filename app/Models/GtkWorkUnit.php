<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkWorkUnit extends Model
{
    use HasFactory;

    protected $table = 'gtk_work_unit';

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
        'user_id',
        'work_unit_id',
        'jabatan',
        'is_primary',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'work_unit_id' => 'string',
        'is_primary' => 'boolean',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    // SCOPES
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByWorkUnit($query, $workUnitId)
    {
        return $query->where('work_unit_id', $workUnitId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
