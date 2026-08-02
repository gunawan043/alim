<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ApprovalAction extends Model
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
        'approval_request_id',
        'step_order',
        'role_name',
        'step_permission',
        'approved_by',
        'action',
        'action_at',
        'note',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = [
        'note',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'id' => 'string',
        'approval_request_id' => 'string',
        'step_order' => 'integer',
        'approved_by' => 'string',
        'action_at' => 'datetime',
    ];

    // RELATIONSHIPS
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ENCRYPTED FIELDS
    protected function note(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    // SCOPES
    public function scopePending($query)
    {
        return $query->where('action', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('action', 'APPROVED');
    }

    public function scopeRejected($query)
    {
        return $query->where('action', 'REJECTED');
    }

    public function scopeByStep($query, $stepOrder)
    {
        return $query->where('step_order', $stepOrder);
    }

    // ACTION MANAGEMENT
    public function approve($userId, $note = null)
    {
        $this->action = 'APPROVED';
        $this->approved_by = $userId;
        $this->action_at = now();
        $this->note = $note;
        $this->ip_address = request()->ip();
        $this->user_agent = request()->userAgent();
        $this->save();

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'APPROVAL_ACTION_APPROVED',
            'table_name' => 'approval_actions',
            'record_id' => $this->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function reject($userId, $note = null)
    {
        $this->action = 'REJECTED';
        $this->approved_by = $userId;
        $this->action_at = now();
        $this->note = $note;
        $this->ip_address = request()->ip();
        $this->user_agent = request()->userAgent();
        $this->save();

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'APPROVAL_ACTION_REJECTED',
            'table_name' => 'approval_actions',
            'record_id' => $this->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // ACCESSORS
    public function getActionTextAttribute()
    {
        $actions = [
            'PENDING' => 'Menunggu',
            'APPROVED' => 'Disetujui',
            'REJECTED' => 'Ditolak',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    public function getActionColorAttribute()
    {
        $colors = [
            'PENDING' => 'warning',
            'APPROVED' => 'success',
            'REJECTED' => 'danger',
        ];

        return $colors[$this->action] ?? 'secondary';
    }

    public function getIsPendingAttribute()
    {
        return $this->action === 'PENDING';
    }

    public function getIsApprovedAttribute()
    {
        return $this->action === 'APPROVED';
    }

    public function getIsRejectedAttribute()
    {
        return $this->action === 'REJECTED';
    }
}
