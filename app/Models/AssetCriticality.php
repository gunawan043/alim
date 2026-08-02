<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCriticality extends Model
{
    protected $table = 'asset_criticalities';

    protected $fillable = [
        'asset_id',
        'criticality',
        'score',
        'factors',
    ];

    protected $casts = [
        'factors' => 'array',
    ];

    public static function fromScore(int $score): string
    {
        return match (true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };
    }
}
