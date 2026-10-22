<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructuralPositionWorkUnitHour extends Model
{
    use HasUuids;

    protected $table = 'structural_position_work_unit_hours';

    protected $fillable = ['structural_position_id', 'work_unit_id', 'weekly_hours'];

    protected $casts = ['weekly_hours' => 'decimal:2'];

    public function position(): BelongsTo { return $this->belongsTo(StructuralPosition::class, 'structural_position_id'); }
    public function workUnit(): BelongsTo { return $this->belongsTo(WorkUnit::class); }
}
