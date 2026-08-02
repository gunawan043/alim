<?php

namespace App\Models\Uks;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UkShiftAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'uk_shift_assignments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'assigned_to_id',
        'created_by_id',
        'shift_date',
        'shift_type',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
