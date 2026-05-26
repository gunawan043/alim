<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'locked_at' => 'datetime',
        'locked_until' => 'datetime',
        'is_active' => 'boolean',
        'failed_login_attempts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // RELATIONSHIPS
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
                $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
                return $maskedUsername . '@' . $domain;
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

    public function incrementFailedLoginAttempts()
    {
        $this->failed_login_attempts += 1;

        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addHours(24);
            $this->locked_at = now();
        }

        $this->save();
    }

    public function resetFailedLoginAttempts()
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
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'USER_CREATED',
                'table_name' => 'users',
                'record_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updated(function ($user) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'USER_UPDATED',
                'table_name' => 'users',
                'record_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::deleted(function ($user) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'USER_DELETED',
                'table_name' => 'users',
                'record_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }


    public function gtkEmployment()
    {
        return $this->employment(); // Alias ke employment
    }

    public function gtkContact()
    {
        return $this->hasOne(GtkContact::class);
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
            return substr($nik, 0, 6) . '••••••••' . substr($nik, -2);
        }
        return str_repeat('•', 16);
    }

    public function getMaskedNoKkAttribute()
    {
        $no_kk = $this->no_kk;
        if ($no_kk && strlen($no_kk) >= 16) {
            return substr($no_kk, 0, 4) . '••••••••' . substr($no_kk, -4);
        }
        return str_repeat('•', 16);
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
}
