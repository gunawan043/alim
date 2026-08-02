<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianSkill extends Model
{
    protected $table = 'technician_skills';

    protected $fillable = [
        'user_id',
        'category_slug',
        'proficiency',
        'is_certified',
    ];

    protected $casts = [
        'is_certified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proficiencyScore(): int
    {
        return match ($this->proficiency) {
            'master' => 100,
            'expert' => 80,
            'intermediate' => 50,
            'novice' => 25,
            default => 0,
        };
    }
}
