<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GtkTransferRequest extends Model
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
        'user_id',
        'from_work_unit_id',
        'to_work_unit_id',
        'jabatan',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_note',
        'request_ip',
        'request_user_agent',
    ];

    protected $hidden = [
        'approval_note',
        'request_ip',
        'request_user_agent',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'from_work_unit_id' => 'string',
        'to_work_unit_id' => 'string',
        'requested_by' => 'string',
        'approved_by' => 'string',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromWorkUnit()
    {
        return $this->belongsTo(WorkUnit::class, 'from_work_unit_id');
    }

    public function toWorkUnit()
    {
        return $this->belongsTo(WorkUnit::class, 'to_work_unit_id');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ENCRYPTED FIELDS
    protected function approvalNote(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function reason(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    // SCOPES
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByWorkUnit($query, $workUnitId)
    {
        return $query->where('from_work_unit_id', $workUnitId)
            ->orWhere('to_work_unit_id', $workUnitId);
    }

    // STATUS MANAGEMENT
    public function approve($approverId, $note = null)
    {
        DB::transaction(function () use ($approverId, $note) {
            $this->status = 'APPROVED';
            $this->approved_by = $approverId;
            $this->approved_at = now();
            $this->approval_note = $note;
            $this->save();

            $this->applyTransferToUser($approverId);

            AuditLog::create([
                'user_id' => $approverId,
                'action' => 'TRANSFER_REQUEST_APPROVED',
                'table_name' => 'gtk_transfer_requests',
                'record_id' => $this->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Cascade the approved transfer into gtk_work_unit and history.
     * Switches the GTK's primary work_unit assignment to to_work_unit_id
     * and records the move in gtk_work_unit_histories.
     */
    protected function applyTransferToUser(string $approverId): void
    {
        if (empty($this->to_work_unit_id)) {
            return;
        }

        $userId = $this->user_id;

        $currentPrimary = GtkWorkUnit::where('user_id', $userId)
            ->where('is_primary', true)
            ->first();

        $fromWorkUnitId = $currentPrimary?->work_unit_id ?: $this->from_work_unit_id;

        // If GTK already has an assignment at the destination unit, just promote it
        // to primary (and demote the existing primary). Otherwise create a new record.
        $destination = GtkWorkUnit::where('user_id', $userId)
            ->where('work_unit_id', $this->to_work_unit_id)
            ->first();

        if ($destination) {
            $destination->is_primary = true;
            $destination->jabatan = $this->jabatan ?: $destination->jabatan;
            $destination->save();
        } else {
            GtkWorkUnit::create([
                'user_id' => $userId,
                'work_unit_id' => $this->to_work_unit_id,
                'jabatan' => $this->jabatan,
                'is_primary' => true,
            ]);
        }

        if ($currentPrimary && $currentPrimary->work_unit_id !== $this->to_work_unit_id) {
            $currentPrimary->is_primary = false;
            $currentPrimary->save();
        }

        GtkWorkUnitHistory::create([
            'user_id' => $userId,
            'from_work_unit_id' => $fromWorkUnitId,
            'to_work_unit_id' => $this->to_work_unit_id,
            'jabatan' => $this->jabatan,
            'action' => 'TRANSFER',
            'reason' => $this->reason,
            'performed_by' => $approverId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function reject($approverId, $note = null)
    {
        $this->status = 'REJECTED';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->approval_note = $note;
        $this->save();

        AuditLog::create([
            'user_id' => $approverId,
            'action' => 'TRANSFER_REQUEST_REJECTED',
            'table_name' => 'gtk_transfer_requests',
            'record_id' => $this->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // ACCESSORS
    public function getStatusTextAttribute()
    {
        $statuses = [
            'PENDING' => 'Menunggu',
            'APPROVED' => 'Disetujui',
            'REJECTED' => 'Ditolak',
            'CANCELLED' => 'Dibatalkan',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'PENDING' => 'warning',
            'APPROVED' => 'success',
            'REJECTED' => 'danger',
            'CANCELLED' => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}
