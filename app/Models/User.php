<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'is_active',
        'is_system_admin',
        'is_permanent',
        'last_login_at',
        'google_id',
        'no_kk',
        'nik_wali',
        'no_hp',
        'hubungan',
        'is_wali',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'google_token',
    ];

    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'locked_at' => 'datetime',
        'locked_until' => 'datetime',
        'is_active' => 'boolean',
        'is_system_admin' => 'boolean',
        'is_permanent' => 'boolean',
        'is_wali' => 'boolean',
        'failed_login_attempts' => 'integer',
        'google_id' => 'string',
        'no_kk' => 'string',
        'nik_wali' => 'string',
        'no_hp' => 'string',
        'hubungan' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Wali Relations ───────────────────────────────────────────────────────

    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            WaliSantri::class,
            'user_id',
            'id',
            'id',
            'student_id'
        )->where('wali_santri.status', WaliSantri::STATUS_ACTIVE);
    }

    // ── Relations ─────────────────────────────────────────────────────────────
    public function gtkProfile()
    {
        return $this->hasOne(GtkProfile::class);
    }

    public function gtkWorkUnits()
    {
        return $this->hasMany(GtkWorkUnit::class);
    }

    public function primaryWorkUnit()
    {
        return $this->hasOne(GtkWorkUnit::class)->where('is_primary', true);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function divisiSubscriptions()
    {
        return $this->belongsToMany(Divisi::class, 'user_divisi_subscriptions')
            ->withTimestamps();
    }

    public function subscribedDivisis(): BelongsToMany
    {
        return $this->divisiSubscriptions();
    }

    public function workUnitHistories()
    {
        return $this->hasMany(GtkWorkUnitHistory::class);
    }

    public function transferRequests()
    {
        return $this->hasMany(GtkTransferRequest::class, 'user_id');
    }

    public function performedTransfers()
    {
        return $this->hasMany(GtkTransferRequest::class, 'performed_by');
    }

    public function approvalRequests()
    {
        return $this->hasMany(ApprovalRequest::class, 'requested_by');
    }

    public function approvalActions()
    {
        return $this->hasMany(ApprovalAction::class, 'approved_by');
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function secureAccessTokens()
    {
        return $this->hasMany(SecureAccessToken::class);
    }

    public function passwordOtps()
    {
        return $this->hasMany(PasswordOtp::class);
    }

    public function employment()
    {
        return $this->hasOne(GtkEmployment::class);
    }

    public function employments()
    {
        return $this->hasMany(GtkEmployment::class, 'user_id');
    }

    public function competencies()
    {
        return $this->hasMany(GtkCompetency::class);
    }

    public function trainings()
    {
        return $this->hasMany(GtkTraining::class);
    }

    public function additionalTasks()
    {
        return $this->hasMany(GtkAdditionalTask::class);
    }

    public function careerPaths()
    {
        return $this->hasMany(GtkCareerPath::class);
    }

    public function pension()
    {
        return $this->hasOne(GtkPension::class, 'user_id');
    }

    public function educations()
    {
        return $this->hasMany(GtkEducation::class);
    }

    public function contact()
    {
        return $this->hasOne(GtkContact::class);
    }

    public function roomSupervisors()
    {
        return $this->hasMany(RoomSupervisor::class, 'user_id');
    }

    public function activeRoomSupervisions()
    {
        return $this->hasMany(RoomSupervisor::class, 'user_id')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where('start_date', '<=', now()->toDateString());
    }

    // NOTE: 'password' is already hashed via the 'password' => 'hashed' cast.
    // Do NOT use Hash::make() in seeders — pass the plain password.

    public function getMaskedEmailAttribute()
    {
        $email = $this->email;
        $parts = explode('@', $email);
        if (count($parts) == 2) {
            $username = $parts[0];
            $domain = $parts[1];

            if (strlen($username) > 2) {
                $maskedUsername = substr($username, 0, 2).str_repeat('*', strlen($username) - 2);

                return $maskedUsername.'@'.$domain;
            }
        }

        return $email;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_until')
            ->where('locked_until', '>', now());
    }

    public function incrementFailedLoginAttempts(): void
    {
        $this->failed_login_attempts += 1;

        if ($this->failed_login_attempts >= 9) {
            $this->locked_until = now()->addHours(24);
            $this->locked_at = now();
        }

        $this->save();
    }

    public function resetFailedLoginAttempts(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    public function isLocked()
    {
        return $this->locked_until && $this->locked_until > now();
    }

    // AUDIT LOGGING
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        static::created(function ($user) {
            static::writeAuditLog($user, 'USER_CREATED');
        });

        static::updated(function ($user) {
            static::writeAuditLog($user, 'USER_UPDATED');
        });

        static::deleted(function ($user) {
            static::writeAuditLog($user, 'USER_DELETED');
        });
    }

    /**
     * Write a USER_* audit log entry. If the authenticated user referenced by
     * the request no longer exists (e.g. after migrate:fresh with a stale
     * session), null is stored in user_id rather than crashing the FK.
     */
    private static function writeAuditLog(self $user, string $action): void
    {
        $actorId = auth()->id();

        if ($actorId !== null && ! DB::table('users')->where('id', $actorId)->exists()) {
            $actorId = null;
        }

        AuditLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'table_name' => 'users',
            'record_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function gtkEmployment()
    {
        return $this->employment(); // Alias ke employment
    }

    public function gtkContact()
    {
        return $this->hasOne(GtkContact::class);
    }

    public function gtkHealthData()
    {
        return $this->hasOne(GtkHealthData::class);
    }

    public function workUnits()
    {
        return $this->gtkWorkUnits();
    }

    public function gtkEducations()
    {
        return $this->hasMany(GtkEducation::class, 'user_id');
    }

    public function getMaskedNikAttribute()
    {
        $nik = $this->nik;
        if ($nik && strlen($nik) >= 16) {
            return substr($nik, 0, 6).'••••••••'.substr($nik, -2);
        }

        return str_repeat('•', 16);
    }

    public function getMaskedNoKkAttribute()
    {
        $no_kk = $this->no_kk;
        if ($no_kk && strlen($no_kk) >= 16) {
            return substr($no_kk, 0, 4).'••••••••'.substr($no_kk, -4);
        }

        return str_repeat('•', 16);
    }

    // ── Wali-Santri relationships ──────────────────────────────────────────

    public function waliSantri()
    {
        return $this->hasMany(WaliSantri::class, 'user_id');
    }

    public function activeWaliSantri()
    {
        return $this->hasMany(WaliSantri::class, 'user_id')->where('status', 'active');
    }

    public function linkedStudents()
    {
        return $this->belongsToMany(
            Student::class,
            'wali_santri',
            'user_id',
            'student_id'
        )->withPivot(['role', 'is_primary', 'status', 'created_at'])
            ->wherePivot('status', 'active')
            ->withTimestamps();
    }

    public function verifiedWaliLinks()
    {
        return $this->hasMany(WaliSantri::class, 'verified_by');
    }

    public function recruitmentProfile()
    {
        return $this->hasOne(RecruitmentProfile::class);
    }

    public function verifiedEducations()
    {
        return $this->hasMany(RecruitmentEducation::class, 'verified_by');
    }

    public function verifiedTrainings()
    {
        return $this->hasMany(RecruitmentTraining::class, 'verified_by');
    }

    public function verifiedDocuments()
    {
        return $this->hasMany(RecruitmentDocument::class, 'verified_by');
    }

    public function createdJobs()
    {
        return $this->hasMany(RecruitmentJob::class, 'created_by');
    }

    public function approvedJobs()
    {
        return $this->hasMany(RecruitmentJob::class, 'approved_by');
    }

    public function processedApplications()
    {
        return $this->hasMany(RecruitmentApplication::class, 'processed_by');
    }

    // ── AUTH CONTRACT (Sprint 1, ADR-018) ────────────────────────────────────
    //
    // Callers MUST go through effectiveRoles() rather than calling
    // Spatie's ->roles / ->getRoleNames directly. This keeps the role
    // abstraction centralised and lets us swap the source-of-truth later
    // (e.g. merge legacy DB roles with Spatie-cached roles) without
    // scattering that knowledge across controllers.

    public function isSystemAdmin(): bool
    {
        return (bool) ($this->is_system_admin ?? false);
    }

    public function isPermanent(): bool
    {
        return (bool) ($this->is_permanent ?? false);
    }

    /**
     * @return list<string> Sorted, unique role identifiers.
     */
    public function effectiveRoles(): array
    {
        try {
            $roles = $this->getRoleNames();
        } catch (\Throwable) {
            $roles = collect();
        }

        $names = $roles
            ->map(static fn ($name) => strtolower(trim((string) $name)))
            ->filter(static fn ($name) => $name !== '')
            ->unique()
            ->values()
            ->all();

        sort($names);

        return $names;
    }

    /**
     * Daftar role yang termasuk dalam kategori "user asrama"
     * (termasuk admin asrama, kepala asrama, wali asrama, dsb.).
     * Role-role ini tidak boleh CRUD data Santri tapi tetap bisa CRUD Mahrom
     * dan hanya melihat Santri yang mondok (active DormitoryResident).
     *
     * @var string[]
     */
    public const DORMITORY_ROLES = [
        'kepala asrama',
        'admin asrama',
        'admin pendidikan',
        'kepala uks',
        'admin uks putra',
        'admin uks putri',
        'admin kesehatan',
        'wali asrama',
        'asrama',
    ];

    /**
     * Tentukan apakah user saat ini termasuk kategori user asrama.
     */
    public function isDormitoryUser(): bool
    {
        $roles = $this->effectiveRoles();
        if (empty($roles)) {
            return false;
        }

        return (bool) array_intersect($roles, self::DORMITORY_ROLES);
    }
}
