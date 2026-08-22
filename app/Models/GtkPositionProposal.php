<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GtkPositionProposal extends Model
{
    protected $table = 'gtk_position_proposals';

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
        'current_employment_id',
        'proposed_position_id',
        'proposed_jabatan_text',
        'proposed_school_id',
        'proposed_work_unit',
        'reason',
        'proposal_type',
        'status',
        'proposed_by',
        'proposer_role_at_submit',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'nomor_sk',
        'tmt',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'tmt' => 'date',
    ];

    public const TYPE_LABELS = [
        'promosi' => 'Promosi',
        'demosi' => 'Demosi',
        'rotasi' => 'Rotasi',
        'mutasi' => 'Mutasi',
        'penugasan' => 'Penugasan Baru',
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'submitted' => 'Diajukan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function currentEmployment(): BelongsTo
    {
        return $this->belongsTo(GtkEmployment::class, 'current_employment_id');
    }

    public function proposedPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'proposed_position_id');
    }

    public function proposedSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'proposed_school_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->proposal_type] ?? $this->proposal_type;
    }

    public function getProposalTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->proposal_type] ?? $this->proposal_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'info',
        };
    }

    protected function nomorSk(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeByProposer($query, string $userId)
    {
        return $query->where('proposed_by', $userId);
    }

    public function scopeBySubject($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
