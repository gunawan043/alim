<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DormitoryStaffAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'dormitory_staff_assignments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'tmt' => 'date',
        'tst' => 'date',
        'is_active' => 'boolean',
        'duties' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'user_id',
        'dormitory_id',
        'assigned_by_id',
        'start_date',
        'end_date',
        'status',
        'position',
        'position_id',
        'shift',
        'duties',
        'nomor_sk',
        'tmt',
        'tst',
        'notes',
        'updated_by_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Jabatan mengikuti master struktur organisasi, bukan teks bebas.
     *
     * Dinamai `structuralPosition` karena kolom legacy `position` masih
     * menyimpan slug (`wakil_kepala_asrama`, dst.) dan akan menutupi relasi
     * bila dinamai `position`.
     */
    public function structuralPosition(): BelongsTo
    {
        return $this->belongsTo(StructuralPosition::class, 'position_id');
    }

    /**
     * Tugas tambahan mengikuti master jenis tugas tambahan.
     */
    public function taskTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            AdditionalTaskType::class,
            'dormitory_staff_assignment_tasks',
            'assignment_id',
            'additional_task_type_id',
        )->withPivot('hours_per_week')->withTimestamps();
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'dormitory_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereNotNull('end_date')
                            ->whereRaw('DATE(end_date) >= ?', [$today]);
                    });
            })
            ->whereRaw('DATE(start_date) <= ?', [$today]);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDormitory($query, string $dormitoryId)
    {
        return $query->where('dormitory_id', $dormitoryId);
    }
}
