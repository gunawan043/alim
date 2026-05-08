<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NotificationUniversal extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $table = 'notifications_universal';

    protected $fillable = [
        'user_id', 'module', 'reference_type', 'reference_id', 'reference_code',
        'type', 'action', 'title', 'message', 'data',
        'is_read', 'read_at', 'is_archived', 'archived_at',
        'is_email_sent', 'email_sent_at', 'is_whatsapp_sent', 'whatsapp_sent_at',
        'is_push_sent', 'push_sent_at',
        'action_url', 'action_text', 'priority', 'expires_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_archived' => 'boolean',
        'is_email_sent' => 'boolean',
        'is_whatsapp_sent' => 'boolean',
        'is_push_sent' => 'boolean',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
        'push_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Scopes
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    /**
     * Helper: label readable untuk module
     */
    public function getModuleLabelAttribute()
    {
        return [
            'recruitment' => 'Rekrutmen',
            'gtk'         => 'GTK',
            'work_unit'   => 'Satuan Kerja',
            'career'      => 'Jenjang Karir',
            'approval'    => 'Persetujuan',
            'system'      => 'Sistem',
            'transfer'    => 'Mutasi',
            'education'   => 'Pendidikan',
            'competency'  => 'Kompetensi',
            'training'    => 'Pelatihan',
        ][$this->module] ?? ucfirst($this->module ?? 'Lainnya');
    }

    /**
     * Helper: label readable untuk priority
     */
    public function getPriorityLabelAttribute()
    {
        return [
            'low'    => '<span class="badge bg-secondary">Rendah</span>',
            'medium' => '<span class="badge bg-info">Sedang</span>',
            'high'   => '<span class="badge bg-warning">Tinggi</span>',
            'urgent' => '<span class="badge bg-danger">Urgent</span>',
        ][$this->priority] ?? '';
    }

    /**
     * Helper: icon untuk module
     */
    public function getModuleIconAttribute()
    {
        return [
            'recruitment' => 'bx bx-user-plus',
            'gtk'         => 'bx bx-chalkboard',
            'work_unit'   => 'bx bx-buildings',
            'career'      => 'bx bxTrending-up',
            'approval'    => 'bx bxCheck',
            'system'      => 'bx bx-cog',
            'transfer'    => 'bx bx-log-out-circle',
            'education'   => 'bx bx-book-open',
            'competency'  => 'bx bx-star',
            'training'    => 'bx bx-graduation',
        ][$this->module] ?? 'bx bx-bell';
    }

    /**
     * Helper: badge color untuk type
     */
    public function getTypeBadgeClassAttribute()
    {
        return [
            'info'    => 'bg-info-subtle text-info',
            'success' => 'bg-success-subtle text-success',
            'warning' => 'bg-warning-subtle text-warning',
            'error'   => 'bg-danger-subtle text-danger',
        ][$this->type] ?? 'bg-info-subtle text-info';
    }
}