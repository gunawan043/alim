<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentMutationOut extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'student_mutations_out';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'student_id', 'school_id', 'status', 'out_type',
        'graduation_year', 'graduation_certificate_number', 'graduation_school_name',
        'institution_name', 'institution_address', 'institution_phone', 'institution_email',
        'head_name', 'head_title', 'head_nupy',
        'letter_number',
        'established_city', 'established_date', 'hijri_date',
        'student_nisn', 'student_nis', 'student_name', 'student_gender',
        'student_birth_date', 'student_birth_place', 'student_address',
        'student_previous_school', 'student_current_class',
        'parent_name', 'parent_occupation', 'parent_address', 'parent_phone',
        'destination_school_name', 'destination_school_address',
        'reason', 'notes',
        'requested_by', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'established_date'   => 'date',
        'student_birth_date' => 'date',
        'approved_at'        => 'datetime',
        'graduation_year'    => 'integer',
    ];

    protected $appends = ['status_text', 'status_color', 'gender_text', 'out_type_text'];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'submitted' => 'Tercadangkan',
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            default     => ucfirst($this->status ?? ''),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'secondary',
            'submitted' => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
            default     => 'secondary',
        };
    }

    public function getGenderTextAttribute(): string
    {
        return match ($this->student_gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    public function getOutTypeTextAttribute(): string
    {
        return match ($this->out_type) {
            'mutation' => 'Mutasi Keluar',
            'dropout' => 'Drop Out',
            'graduation' => 'Lulus',
            default => ucfirst($this->out_type ?? ''),
        };
    }

    public function getOutTypeColorAttribute(): string
    {
        return match ($this->out_type) {
            'mutation' => 'info',
            'dropout' => 'danger',
            'graduation' => 'success',
            default => 'secondary',
        };
    }

    public function getHijriDateAttribute(): ?string
    {
        // Priority: stored DB value > computed from established_date
        if (isset($this->attributes['hijri_date']) && $this->attributes['hijri_date'] !== '') {
            return $this->attributes['hijri_date'];
        }
        if (!$this->established_date) return null;
        $monthsID = [
            'Muharram','Safar','Rabiul Awwal','Rabiul Akhir',
            'Jumadil Awwal','Jumadil Akhir','Rajab','Syakban',
            'Ramadan','Syawal','Dzulqa\'dah','Dzulhijjah',
        ];
        try {
            $d = $this->established_date->copy()->locale('ar');
            $monthIdx = (int) $d->format('n') - 1;
            $monthName = $monthsID[$monthIdx] ?? '';
            return $d->format('j') . ' ' . $monthName . ' ' . $d->format('jFY') . ' H';
        } catch (\Throwable) {
            return null;
        }
    }
}
