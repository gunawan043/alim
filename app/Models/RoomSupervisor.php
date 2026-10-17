<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RoomSupervisor extends Model
{
    use SoftDeletes;

    protected $table = 'room_supervisors';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'user_id',
        'room_id',
        'dormitory_id',
        'academic_year_id',
        'decree_id',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class, 'room_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'dormitory_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function decree(): BelongsTo
    {
        return $this->belongsTo(InstitutionDecree::class, 'decree_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where('start_date', '<=', now()->toDateString());
    }

    public function scopeCurrent($query)
    {
        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        return $query->where(function ($q) use ($activeYearId) {
            $q->where('status', 'active')
                ->where(function ($q2) {
                    $q2->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
                })
                ->where('start_date', '<=', now()->toDateString());

            if ($activeYearId) {
                $q->orWhere('academic_year_id', $activeYearId);
            }
        });
    }
}
