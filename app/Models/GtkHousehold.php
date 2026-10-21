<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkHousehold extends Model
{
    protected $table = 'gtk_households';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['label', 'created_by'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id ??= (string) Str::uuid());
    }

    public function profiles()
    {
        return $this->hasMany(GtkProfile::class, 'household_id');
    }

    public function members()
    {
        return $this->hasMany(GtkFamilyMember::class, 'household_id');
    }
}
