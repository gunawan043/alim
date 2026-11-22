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
        'position_id',
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

    /**
     * Immutable period history for this placement.
     */
    public function periods()
    {
        return $this->hasMany(GtkWorkUnitPeriod::class, 'placement_id')
            ->orderBy('start_date');
    }

    public function activePeriod()
    {
        return $this->hasOne(GtkWorkUnitPeriod::class, 'placement_id')
            ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
            ->whereNull('end_date')
            ->orderByDesc('start_date');
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /**
     * Jabatan struktural pada satuan kerja ini (master structural_positions).
     */
    public function position()
    {
        return $this->belongsTo(StructuralPosition::class, 'position_id');
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

    /**
     * Penempatan yang dianggap masih berlaku.
     *
     * Saat lifecycle aktif, baris yang seluruh periodenya sudah ditutup tidak
     * lagi dihitung sebagai penempatan berjalan. Baris legacy yang belum punya
     * periode sama sekali tetap dipertahankan agar data lama tidak hilang
     * sebelum dimigrasikan (tidak ada backfill pada Batch 1).
     *
     * Saat flag mati, scope ini sengaja tidak mengubah query apa pun sehingga
     * perilaku pembaca lama tetap sama.
     */
    public function scopeCurrent($query)
    {
        if (! config('gtk_work_unit.lifecycle.enabled', false)) {
            return $query;
        }

        return $query->where(function ($q) {
            $q->whereHas('activePeriod')
                ->orWhereDoesntHave('periods');
        });
    }
}
