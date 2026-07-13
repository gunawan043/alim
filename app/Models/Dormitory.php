<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dormitory extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (! $m->id) {
                $m->id = (string) \Illuminate\Support\Str::uuid();
            }
            // Auto-generate code jika belum ada
            if (! $m->code) {
                $m->code = self::generateUniqueCode($m->gender);
            }
            // Auto-fill name dari WorkUnit, ganti "Pengasuhan" → "Asrama"
            if (! $m->name && $m->work_unit_id) {
                $wu = \App\Models\WorkUnit::find($m->work_unit_id);
                if ($wu) {
                    $m->name = str_replace('Pengasuhan', 'Asrama', $wu->name);
                }
            }
        });
    }

    /**
     * Generate unique dormitory code per gender.
     * Format: PUTRA-001, PUTRI-001, dst.
     */
    public static function generateUniqueCode(string $gender = 'putra'): string
    {
        $prefix = strtoupper($gender);
        $last = self::where('gender', $gender)
            ->where('code', 'LIKE', "{$prefix}-%")
            ->orderBy('code', 'desc')
            ->value('code');

        $number = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $match)) {
            $number = intval($match[1]) + 1;
        }

        return $prefix.'-'.str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        'work_unit_id',
        'school_id',
        'code',
        'name',
        'gender',
        'address',
        'phone',
        'capacity',
        'total_rooms',
        'total_wings',
        'head_id',
        'is_active',
        'logo_path',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'total_rooms' => 'integer',
        'total_wings' => 'integer',
        'is_active' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function wings(): HasMany
    {
        return $this->hasMany(DormitoryWing::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(DormitoryRoom::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(DormitoryResident::class);
    }

    public function permits(): HasMany
    {
        return $this->hasMany(DormitoryPermit::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(DormitoryViolation::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(DormitoryPost::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(DormitoryInventory::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(DormitoryEmergencyBroadcast::class);
    }

    public function policyAssignments(): HasMany
    {
        return $this->hasMany(DormitoryPolicyAssignment::class, 'target_id')
            ->where('policy_assignment_type', 'dormitory');
    }
}
