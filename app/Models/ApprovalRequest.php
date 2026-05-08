<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
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
    ];

    protected $casts = [
        'id' => 'string',
        'reference_id' => 'string',
        'requested_by' => 'string',
    ];

    // RELATIONSHIPS
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class)->orderBy('step_order');
    }

    public function currentStep()
    {
        return $this->actions()->where('action', 'PENDING')->orderBy('step_order')->first();
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