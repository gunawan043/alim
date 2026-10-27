<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationUniversal extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'notifications_universal';

    protected $fillable = [
        'user_id', 'module', 'reference_type', 'reference_id', 'reference_code',
        'type', 'action', 'title', 'message', 'data',
        'is_read', 'read_at', 'is_archived', 'archived_at',
        'is_email_sent', 'email_sent_at', 'is_whatsapp_sent', 'whatsapp_sent_at',
        'is_push_sent', 'push_sent_at',
        'action_url', 'action_text', 'priority', 'expires_at',
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
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        });
    }

    /**
     * URL aksi notifikasi.
     *
     * Notifikasi lama dapat menyimpan URL absolut (mis. APP_URL lokal
     * http://127.0.0.1:8000/...). Saat aplikasi diakses dari host lain,
     * URL tersebut membuat browser pindah host dan sesi login hilang.
     * Untuk URL internal, kembalikan path relatif agar tetap memakai host
     * yang sedang dibuka pengguna. URL eksternal dibiarkan apa adanya.
     */
    public function getActionUrlAttribute($value): ?string
    {
        $url = $value
            ? self::normalizeActionUrl($value, $this->user_id)
            : self::normalizeActionUrl($this->fallbackActionUrl(), $this->user_id);

        // Jika data lama masih berupa nama route / path yang tidak valid,
        // jangan biarkan browser membuka URL yang salah.
        if ($url && ! str_starts_with($url, '/') && ! str_starts_with($url, 'http')) {
            return self::normalizeActionUrl($this->fallbackActionUrl(), $this->user_id);
        }

        return $url;
    }

    /**
     * Tujuan cadangan untuk notifikasi lama yang belum menyimpan action_url.
     * Dipilih berdasarkan modul/aksi agar klik tetap membuka halaman terkait.
     */
    protected function fallbackActionUrl(): ?string
    {
        $userId = $this->user_id ?: auth()->id();
        if (! $userId) {
            return null;
        }

        $action = (string) $this->action;
        $module = (string) $this->module;

        if (in_array($action, ['login', 'password_forgot', 'password_reset', 'password_changed'], true)) {
            return route('user.profile.security', ['userId' => $userId]);
        }

        if ($module === 'kaldik') {
            return route('user.kaldik.index', ['userId' => $userId]);
        }

        if ($module === 'feedback' || str_starts_with($action, 'feedback_')) {
            return route('user.feedback.index', ['userId' => $userId]);
        }

        if ($module === 'announcement' || $action === 'announcement') {
            if ($this->reference_id && \Illuminate\Support\Facades\Route::has('user.announcements.show')) {
                return route('user.announcements.show', [
                    'userId' => $userId,
                    'id' => $this->reference_id,
                ]);
            }

            return route('user.notifications.page', ['userId' => $userId]);
        }

        if ($module === 'recruitment') {
            return route('user.ats.index', ['userId' => $userId]);
        }

        if ($module === 'hrd.cuti') {
            return route('user.cuti.index', ['userId' => $userId]);
        }

        if ($module === 'hrd.kontrak') {
            return route('user.kontrak.expiring', ['userId' => $userId]);
        }

        if ($module === 'hrd.pelatihan') {
            return route('user.pelatihan.index', ['userId' => $userId]);
        }

        if ($module === 'hrd.kinerja') {
            return route('user.kinerja.index', ['userId' => $userId]);
        }

        if ($module === 'hrd.kesejahteraan') {
            return route('user.kesejahteraan.index', ['userId' => $userId]);
        }

        if ($module === 'todo') {
            return route('user.todos.index', ['userId' => $userId]);
        }

        // Notifikasi tanpa referensi halaman: arahkan ke daftar notifikasi,
        // bukan ke '#' yang membuat klik terasa tidak berfungsi.
        return route('user.notifications.page', ['userId' => $userId]);
    }

    /**
     * Ubah URL internal absolut menjadi path relatif agar tidak memindahkan
     * pengguna ke host lama (mis. 127.0.0.1 dari data seed/lokal).
     */
    public static function normalizeActionUrl($value, ?string $userId = null): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $parts = parse_url($value);

        if (! empty($parts['host'])) {
            $host = strtolower($parts['host']);
            $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
            $requestHost = strtolower((string) request()?->getHost());
            $internalHosts = array_filter([
                'localhost',
                '127.0.0.1',
                '::1',
                $appHost,
                $requestHost,
            ]);

            // URL eksternal (mis. portal wali di domain lain) dibiarkan.
            if (! in_array($host, $internalHosts, true)) {
                return $value;
            }

            $value = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        // Nama route lama yang tersimpan apa adanya (mis. cuti.index).
        if (! str_starts_with($value, '/')) {
            $candidates = str_starts_with($value, 'user.') ? [$value] : ['user.'.$value, $value];

            foreach ($candidates as $routeName) {
                if (! \Illuminate\Support\Facades\Route::has($routeName)) {
                    continue;
                }

                try {
                    return self::normalizeActionUrl(route($routeName, array_filter([
                        'userId' => $userId,
                    ])), $userId);
                } catch (\Throwable) {
                    // Coba kandidat berikutnya.
                }
            }

            return $value;
        }

        // Path lama tanpa prefix userId.
        if ($value === '/notifications') {
            return $userId ? '/'.$userId.'/notifications/page' : $value;
        }

        if (preg_match('~^/recruitment/jobs/([^/?#]+)~', $value, $m)) {
            return $userId ? '/'.$userId.'/ats/jobs/'.$m[1] : $value;
        }

        if (preg_match('~^/recruitment/applications/([^/?#]+)~', $value, $m)) {
            return $userId ? '/'.$userId.'/ats/applications/'.$m[1] : $value;
        }

        // Notifikasi Kaldik lama bisa menyimpan userId pembuat, bukan penerima.
        if ($userId) {
            $value = preg_replace(
                '#^/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}(/kaldik(?:/|$))#i',
                '/'.$userId.'$1',
                $value,
                1
            );
        }

        return $value;
    }

    /**
     * Helper: label readable untuk module
     */
    public function getModuleLabelAttribute()
    {
        return [
            'recruitment' => 'Rekrutmen',
            'gtk' => 'GTK',
            'work_unit' => 'Satuan Kerja',
            'career' => 'Jenjang Karir',
            'approval' => 'Persetujuan',
            'system' => 'Sistem',
            'transfer' => 'Mutasi',
            'education' => 'Pendidikan',
            'competency' => 'Kompetensi',
            'training' => 'Pelatihan',
            'osipp' => 'OSIPP',
        ][$this->module] ?? ucfirst($this->module ?? 'Lainnya');
    }

    /**
     * Helper: label readable untuk priority
     */
    public function getPriorityLabelAttribute()
    {
        return [
            'low' => '<span class="badge bg-secondary">Rendah</span>',
            'medium' => '<span class="badge bg-info">Sedang</span>',
            'high' => '<span class="badge bg-warning">Tinggi</span>',
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
            'gtk' => 'bx bx-chalkboard',
            'work_unit' => 'bx bx-buildings',
            'career' => 'bx bxTrending-up',
            'approval' => 'bx bxCheck',
            'system' => 'bx bx-cog',
            'transfer' => 'bx bx-log-out-circle',
            'education' => 'bx bx-book-open',
            'competency' => 'bx bx-star',
            'training' => 'bx bx-graduation',
            'osipp' => 'bx bx-group',
        ][$this->module] ?? 'bx bx-bell';
    }

    /**
     * Helper: badge color untuk type
     */
    public function getTypeBadgeClassAttribute()
    {
        return [
            'info' => 'bg-info-subtle text-info',
            'success' => 'bg-success-subtle text-success',
            'warning' => 'bg-warning-subtle text-warning',
            'error' => 'bg-danger-subtle text-danger',
        ][$this->type] ?? 'bg-info-subtle text-info';
    }
}
