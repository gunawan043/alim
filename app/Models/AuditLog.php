<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

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
        'action',
        'table_name',
        'record_id',
        'record_type',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'created_at' => 'datetime',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function record()
    {
        return $this->morphTo('record', 'record_type', 'record_id');
    }

    // SCOPES
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeLastDays($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    // ACCESSORS
    public function getFormattedActionAttribute()
    {
        $actions = [
            'USER_CREATED' => 'Membuat User',
            'USER_UPDATED' => 'Mengupdate User',
            'USER_DELETED' => 'Menghapus User',
            'GTK_PROFILE_CREATED' => 'Membuat Profil GTK',
            'GTK_PROFILE_UPDATED' => 'Mengupdate Profil GTK',
            'GTK_PROFILE_DELETED' => 'Menghapus Profil GTK',
            'WORK_UNIT_CREATED' => 'Membuat Unit Kerja',
            'WORK_UNIT_UPDATED' => 'Mengupdate Unit Kerja',
            'WORK_UNIT_DELETED' => 'Menghapus Unit Kerja',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    // MASKING SENSITIVE DATA
    public function getMaskedIpAddressAttribute()
    {
        $ip = $this->ip_address;
        if (! $ip) {
            return null;
        }

        $parts = explode('.', $ip);
        if (count($parts) == 4) {
            return $parts[0].'.'.$parts[1].'.***.***';
        }

        return $ip;
    }

    public function getMaskedUserAgentAttribute()
    {
        $ua = $this->user_agent;
        if (! $ua) {
            return null;
        }

        // Ambil hanya browser dan OS
        $browser = '';
        $os = '';

        if (strpos($ua, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($ua, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($ua, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($ua, 'Edge') !== false) {
            $browser = 'Edge';
        }

        if (strpos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($ua, 'Mac') !== false) {
            $os = 'macOS';
        } elseif (strpos($ua, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (strpos($ua, 'iOS') !== false) {
            $os = 'iOS';
        }

        return $browser ? ($os ? "$browser on $os" : $browser) : 'Unknown';
    }
}
