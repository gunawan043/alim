<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentMutationIn extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'student_mutations_in';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'student_id', 'school_id', 'status',
        'institution_name', 'institution_address', 'institution_phone', 'institution_email',
        'head_name', 'head_title', 'head_nip',
        'letter_number', 'recommendation_year',
        'established_city', 'established_date', 'hijri_date',
        'student_nisn', 'student_nis', 'student_name', 'student_gender',
        'student_birth_date', 'student_birth_place', 'student_address',
        'student_rt', 'student_rw', 'student_hamlet', 'student_postal_code',
        'student_province_code', 'student_city_code', 'student_district_code', 'student_village_code',
        'student_previous_school', 'student_previous_class', 'student_current_class',
        'student_religion',
        'parent_name', 'parent_occupation', 'parent_address', 'parent_phone',
        'father_name', 'father_occupation', 'mother_name', 'mother_occupation',
        'accepted_class', 'accepted_semester', 'accepted_academic_year',
        'origin_school_name', 'origin_school_address', 'origin_school_city',
        'reason', 'notes',
        'requested_by', 'approved_by', 'approved_at', 'rejection_reason',
        // Student data (non-prefixed)
        'phone', 'mobile_phone', 'email',
        'residence_type', 'transportation', 'distance_to_school',
        'height', 'weight', 'head_circumference', 'sibling_count',
        'child_number', 'entry_grade_level', 'entry_date',
        'skhun', 'ujian_national_number', 'certificate_number', 'birth_certificate_number',
        'is_kps_receiver', 'kps_number',
        'is_kip_receiver', 'kip_number', 'kip_name',
        'is_pip_eligible', 'kks_number', 'pip_reason',
        'graduation_year', 'graduation_date',
        'bank_name', 'bank_cabang', 'bank_account_number', 'bank_account_name',
        'religion', 'special_needs', 'no_kk', 'nik',
        'father_name', 'father_birth_year', 'father_education', 'father_occupation', 'father_nik', 'father_income',
        'mother_name', 'mother_birth_year', 'mother_education', 'mother_occupation', 'mother_nik', 'mother_income',
        'guardian_name', 'guardian_birth_year', 'guardian_education', 'guardian_occupation', 'guardian_nik', 'guardian_income',
    ];

    protected $casts = [
        'established_date'   => 'date',
        'student_birth_date' => 'date',
        'approved_at'        => 'datetime',
        'entry_date'         => 'date',
        'graduation_date'    => 'date',
        'is_kps_receiver'    => 'boolean',
        'is_kip_receiver'    => 'boolean',
        'is_pip_eligible'    => 'boolean',
        'height'             => 'integer',
        'weight'             => 'integer',
        'head_circumference' => 'integer',
        'sibling_count'      => 'integer',
        'child_number'       => 'integer',
        'distance_to_school' => 'decimal:2',
        'father_income'      => 'decimal:2',
        'mother_income'      => 'decimal:2',
        'guardian_income'    => 'decimal:2',
    ];

    protected $appends = ['status_text', 'status_color', 'gender_text'];

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
}
