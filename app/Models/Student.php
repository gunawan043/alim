<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use App\Services\UnifiedStudentTimelineService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Student extends Model
{
    use LogsDeletion, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'user_id', 'school_id', 'origin_school_id', 'origin_level', 'origin_graduation_year',
        // Identitas
        'nisn', 'nis', 'nik', 'no_kk', 'name', 'name_ar', 'name_ar_status', 'name_ar_reviewed_at', 'name_ar_reviewed_by', 'gender',
        'birth_place', 'birth_date', 'religion', 'special_needs',
        // Alamat
        'address', 'rt', 'rw', 'hamlet',
        'village_code', 'district_code', 'city_code', 'province_code', 'postal_code',
        // Kontak
        'phone', 'mobile_phone', 'email',
        // Tempat tinggal
        'residence_type', 'boarding_status', 'transportation', 'distance_to_school', 'latitude', 'longitude',
        // Kesehatan
        'height', 'weight', 'head_circumference', 'sibling_count',
        // Ayah
        'father_name', 'father_birth_year', 'father_education', 'father_occupation',
        'father_income', 'father_nik',
        // Ibu
        'mother_name', 'mother_birth_year', 'mother_education', 'mother_occupation',
        'mother_income', 'mother_nik',
        // Wali
        'guardian_name', 'guardian_birth_year', 'guardian_education', 'guardian_occupation',
        'guardian_income', 'guardian_nik',
        // Pendaftaran
        'child_number', 'previous_school', 'entry_date', 'entry_grade_level', 'entry_source',
        'exit_reason', 'exit_date',
        'skhun', 'ujian_national_number', 'certificate_number', 'birth_certificate_number',
        // PIP/KIP/KPS
        'is_kps_receiver', 'kps_number',
        'is_kip_receiver', 'kip_number', 'kip_name', 'kks_number',
        'is_pip_eligible', 'pip_reason',
        // Bank
        'bank_name', 'bank_account_number', 'bank_account_name',
        // Status
        'status', 'graduation_year', 'graduation_date', 'alumni_year',
        // Media
        'photo_path',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'name_ar_reviewed_at' => 'datetime',
        'entry_date' => 'date',
        'exit_date' => 'date',
        'origin_graduation_year' => 'integer',
        'graduation_date' => 'date',
        'father_birth_year' => 'integer',
        'mother_birth_year' => 'integer',
        'guardian_birth_year' => 'integer',
        'distance_to_school' => 'decimal:2',
        'father_income' => 'decimal:2',
        'mother_income' => 'decimal:2',
        'guardian_income' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_kps_receiver' => 'boolean',
        'is_kip_receiver' => 'boolean',
        'is_pip_eligible' => 'boolean',
    ];

    protected $appends = ['gender_text', 'status_text', 'special_needs_text'];

    /**
     * Label jalur masuk untuk tampilan.
     */
    public function getEntrySourceTextAttribute(): string
    {
        return match ($this->entry_source) {
            'manual' => 'Tambah manual',
            'import' => 'Import Excel',
            'mutation' => 'Mutasi masuk',
            'guardian_mobile' => 'Registrasi wali (aplikasi)',
            default => '-',
        };
    }

    /**
     * Label alasan keluar untuk tampilan.
     */
    public function getExitReasonTextAttribute(): string
    {
        return match ($this->exit_reason) {
            'mutation' => 'Mutasi keluar',
            'graduation' => 'Lulus',
            'dropout' => 'Drop out',
            default => '-',
        };
    }

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function originSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'origin_school_id');
    }

    /**
     * A student may have a dormitory/resident record (boarding assignment).
     */
    public function dormitoryResident()
    {
        return $this->hasOne(DormitoryResident::class, 'student_id');
    }

    public function activeDormitoryResident()
    {
        return $this->hasOne(DormitoryResident::class, 'student_id')->where('is_active', true);
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_code', 'code');
    }

    public function classHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class)->with('studyGroup');
    }

    public function violationPoints(): HasMany
    {
        return $this->hasMany(ViolationPoint::class);
    }

    public function mahroms(): HasMany
    {
        return $this->hasMany(StudentMahrom::class);
    }

    public function primaryMahrom()
    {
        return $this->hasOne(StudentMahrom::class)->where('is_primary', true);
    }

    public function currentClassHistory()
    {
        return $this->hasOne(StudentClassHistory::class)->latestOfMany();
    }

    public function healthRecord()
    {
        return $this->hasOne(StudentHealthRecord::class);
    }

    // ── Kelengkapan Data ─────────────────────────────────────────
    // Satu daftar acuan dipakai bersama oleh badge Data Santri, halaman
    // detail santri, dan widget dashboard agar definisinya konsisten.

    public const PROFILE_FIELDS = [
        'nis' => 'NIS',
        'nik' => 'NIK',
        'birth_date' => 'Tanggal lahir',
        'father_name' => 'Nama ayah',
        'mother_name' => 'Nama ibu',
    ];

    /**
     * Label field penting yang masih kosong.
     *
     * @return array<int, string>
     */
    public function missingProfileFields(): array
    {
        $missing = [];

        foreach (self::PROFILE_FIELDS as $column => $label) {
            if (blank($this->getAttribute($column))) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function isProfileComplete(): bool
    {
        return $this->missingProfileFields() === [];
    }

    /**
     * Filter santri yang datanya belum lengkap (dipakai daftar Data Santri).
     */
    public function scopeProfileIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            foreach (array_keys(self::PROFILE_FIELDS) as $column) {
                $q->orWhereNull($column)->orWhere($column, '');
            }
        });
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getGenderTextAttribute(): string
    {
        return $this->gender === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'graduate' => 'Lulus',
            'dropped' => 'Dropout',
            'transfer' => 'Pindah',
            'transfer_out' => 'Pindah (Keluar)',
            'transfer_in' => 'Pindah (Masuk)',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getSpecialNeedsTextAttribute(): string
    {
        return match ($this->special_needs) {
            'tidak' => 'Tidak ada',
            'fisik' => 'Fisik',
            'intelektual' => 'Intelektual',
            'mental' => 'Mental',
            'sosial' => 'Sosial',
            default => ucfirst($this->special_needs ?? ''),
        };
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->hamlet,
            $this->village?->name,
            $this->district?->name,
            $this->city?->display_name,
            $this->province?->name,
            $this->postal_code ? "{$this->postal_code}" : null,
        ]);

        return implode(', ', $parts);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        // photo_path stored relative to storage/app/public/students/photos/
        if ($this->photo_path) {
            return asset('storage/'.$this->photo_path);
        }

        // Fallback initials avatar (handled in view layer)
        // return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=' . (($this->gender === 'P') ? 'ff6b9b' : '#4a90e2');
        return null;
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeBySchool($q, $sid)
    {
        return $q->where('school_id', $sid);
    }

    // ── Achievements ────────────────────────────────────────────

    public function achievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    /**
     * Unified timeline events across all modules (boarding, academic, clinic, etc).
     */
    public function timelineEvents(): HasMany
    {
        return $this->hasMany(BoardingTimelineEvent::class)->orderByDesc('event_at');
    }

    /**
     * Convenience accessor for the unified timeline service.
     */
    public function unifiedTimeline(): UnifiedStudentTimelineService
    {
        return new UnifiedStudentTimelineService($this);
    }
}
