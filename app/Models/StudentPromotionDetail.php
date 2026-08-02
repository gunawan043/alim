<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentPromotionDetail extends Model
{
    protected $table = 'student_promotion_details';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'promotion_id',
        'student_id',
        'action',
        'status',
        'error_message',
        'override_grade_shift',
        'notes',
    ];

    protected $casts = [
        'override_grade_shift' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(StudentPromotion::class, 'promotion_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // ── Accessors ────────────────────────────────────────────────────

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'promote' => 'Naik Kelas',
            'retain' => 'Tinggal Kelas',
            'graduate' => 'Lulus',
            'mutate_out' => 'Mutasi Keluar',
            'skip' => 'Dilompati',
            default => ucfirst($this->action ?? ''),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'secondary',
            'success' => 'success',
            'failed' => 'danger',
            default => 'secondary',
        };
    }
}
