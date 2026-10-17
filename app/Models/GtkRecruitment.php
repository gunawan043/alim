<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GtkRecruitment extends Model
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
        'work_unit_id',
        'created_by',
        'jabatan',
        'kebutuhan',
        'kualifikasi',
        'tanggal_dibutuhkan',
        'status',
    ];

    protected $hidden = [
        'kualifikasi',
    ];

    protected $casts = [
        'id' => 'string',
        'work_unit_id' => 'string',
        'created_by' => 'string',
        'kebutuhan' => 'integer',
        'tanggal_dibutuhkan' => 'date',
    ];

    // RELATIONSHIPS
    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ENCRYPTED FIELDS
    protected function kualifikasi(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    // SCOPES
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeActive($query)
    {
        return $query->where('tanggal_dibutuhkan', '>=', now());
    }

    public function scopeByWorkUnit($query, $workUnitId)
    {
        return $query->where('work_unit_id', $workUnitId);
    }

    // ACCESSORS
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'submitted' => 'Terkirim',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'draft' => 'secondary',
            'submitted' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getDaysUntilNeededAttribute()
    {
        return $this->tanggal_dibutuhkan ? now()->diffInDays($this->tanggal_dibutuhkan, false) : null;
    }

    public function getIsUrgentAttribute()
    {
        return $this->days_until_needed !== null && $this->days_until_needed <= 30;
    }
}
