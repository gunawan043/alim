<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApprovalRequest extends Model
{
    use HasFactory;

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
        'request_type',
        'reference_id',
        'requested_by',
        'status',
        'approval_flow_id',
        'requestable_type',
        'requestable_id',
        'current_step_id',
    ];

    protected $casts = [
        'id' => 'string',
        'reference_id' => 'string',
        'requested_by' => 'string',
        'requestable_id' => 'string',
        'current_step_id' => 'string',
        'approval_flow_id' => 'string',
    ];

    // RELATIONSHIPS
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function requestable()
    {
        return $this->morphTo('requestable', 'requestable_type', 'requestable_id');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class)->orderBy('step_order');
    }

    /**
     * Tahap yang sedang menunggu persetujuan (action pertama yang PENDING).
     */
    public function currentStep()
    {
        return $this->actions()->where('action', 'PENDING')->orderBy('step_order')->first();
    }

    /** Relasi currentStep untuk eager loading / akses data. */
    public function currentStepAction()
    {
        return $this->hasOne(ApprovalAction::class, 'approval_request_id', 'id')
            ->where('action', 'PENDING')
            ->orderBy('step_order');
    }

    // SCOPES
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    public function scopeByReference($query, $referenceId)
    {
        return $query->where('reference_id', $referenceId);
    }

    // STATUS MANAGEMENT
    public function isApproved()
    {
        return $this->status === 'APPROVED';
    }

    public function isRejected()
    {
        return $this->status === 'REJECTED';
    }

    public function isPending()
    {
        return $this->status === 'PENDING';
    }

    // ACCESSORS
    public function getRequestTypeTextAttribute()
    {
        $types = [
            'TRANSFER' => 'Permintaan Mutasi',
            'RECRUITMENT' => 'Permintaan Rekrutmen',
            'LEAVE' => 'Permintaan Cuti',
            'TRAINING' => 'Permintaan Pelatihan',
        ];

        return $types[$this->request_type] ?? $this->request_type;
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'PENDING' => 'Menunggu Persetujuan',
            'APPROVED' => 'Disetujui',
            'REJECTED' => 'Ditolak',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
