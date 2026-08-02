<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkRequest extends Model
{
    use HasFactory;
    use LogsDeletion;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    const TYPE_PROCUREMENT = 'procurement';

    const TYPE_TRIAL = 'trial';

    const TYPE_STATUS_INCREASE = 'status_increase';

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id',
        'requested_by',
        'type',
        'academic_year_id',
        'notes',
        'letter_number',
        'letter_subject',
        'letter_attachment',
        'established_city',
        'established_date',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'work_unit_id' => 'string',
        'academic_year_id' => 'string',
        'established_date' => 'date',
        'created_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items()
    {
        return $this->hasMany(GtkRequestItem::class)->orderBy('order');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // ── Accessors ────────────────────────────────────────────────────

    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PROCUREMENT => 'Pengadaan GTK',
            self::TYPE_TRIAL => 'Pengangkatan Percobaan',
            self::TYPE_STATUS_INCREASE => 'Kenaikan Status GTK',
            default => $this->type,
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Tercadangkan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SUBMITTED => 'info',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function totalKebutuhanTambahan(): int
    {
        return $this->items->sum('kebutuhan_tambahan');
    }
}
