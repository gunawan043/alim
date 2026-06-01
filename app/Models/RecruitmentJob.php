<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class RecruitmentJob extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'recruitment_jobs'; // Tambahkan jika nama tabel tidak sesuai default

    protected $fillable = [
        'work_unit_id',
        'kode_lowongan', 
        'judul', 
        'posisi',
        'jenis_pegawai', 
        'status_pegawai', 
        'persyaratan_umum',
        'persyaratan_khusus', 
        'kualifikasi_pendidikan', 
        'kualifikasi_pengalaman',
        'kompetensi_dibutuhkan', 
        'kuota', 
        'kuota_terisi', 
        'deskripsi_pekerjaan',
        'fasilitas', 
        'rentang_gaji', 
        'tanggal_mulai', 
        'tanggal_selesai',
        'status', 
        'tahapan_seleksi', 
        'created_by', 
        'approved_by', 
        'approved_at',
        'location', // Tambahkan jika ada kolom location
        'company_logo' // Tambahkan jika ada kolom company_logo
    ];

    protected $casts = [
        'tanggal_mulai' => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
        'approved_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'persyaratan_umum' => 'array',
        'persyaratan_khusus' => 'array',
        'kualifikasi_pendidikan' => 'array',
        'kualifikasi_pengalaman' => 'array',
        'kompetensi_dibutuhkan' => 'array',
        'rentang_gaji' => 'array',
        'fasilitas' => 'array', // Tambahkan jika ada
        'tahapan_seleksi' => 'array',
        'kuota' => 'integer',
        'kuota_terisi' => 'integer',
        'is_active' => 'boolean', // Tambahkan jika ada
    ];

    protected $attributes = [
        'status' => 'draft',
        'kuota_terisi' => 0,
    ];

    // $casts sudah handle semua date/datetime field (Laravel 8+ preferensi)

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate kode_lowongan jika belum ada
            if (empty($model->kode_lowongan)) {
                $model->kode_lowongan = static::generateJobCode();
            }
            
            // Set default status jika belum ada
            if (empty($model->status)) {
                $model->status = 'draft';
            }
        });

        static::updating(function ($model) {
            // Logika tambahan saat update jika diperlukan
        });
    }

    /**
     * Generate unique job code
     */
    public static function generateJobCode()
    {
        $prefix = 'LOW';
        $year = date('Y');
        $month = date('m');
        
        $lastJob = static::where('kode_lowongan', 'LIKE', "{$prefix}-{$year}{$month}%")
            ->orderBy('kode_lowongan', 'desc')
            ->first();

        if ($lastJob) {
            $lastNumber = intval(substr($lastJob->kode_lowongan, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$year}{$month}{$newNumber}";
    }

    // ============== RELATIONSHIPS ==============

    /**
     * Get the work unit that owns the job
     */
    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class, 'work_unit_id', 'id');
    }

    /**
     * Get the creator of the job
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the approver of the job
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the applications for the job
     */
    public function applications()
    {
        return $this->hasMany(RecruitmentApplication::class, 'recruitment_job_id');
    }

    /**
     * Get the applications count
     */
    public function applicationsCount()
    {
        return $this->applications()->count();
    }

    // ============== ACCESSORS & MUTATORS ==============

    /**
     * Get kualifikasi_pendidikan as array
     */
    public function getKualifikasiPendidikanAttribute($value)
    {
        return $this->safeJsonDecode($value);
    }

    /**
     * Get kompetensi_dibutuhkan as array
     */
    public function getKompetensiDibutuhkanAttribute($value)
    {
        return $this->safeJsonDecode($value);
    }

    /**
     * Get persyaratan_umum as array
     */
    public function getPersyaratanUmumAttribute($value)
    {
        return $this->safeJsonDecode($value);
    }

    /**
     * Get persyaratan_khusus as array
     */
    public function getPersyaratanKhususAttribute($value)
    {
        return $this->safeJsonDecode($value);
    }

    /**
     * Get fasilitas as array
     */
    public function getFasilitasAttribute($value)
    {
        return $this->safeJsonDecode($value);
    }

    /**
     * Get rentang_gaji as array
     */
    public function getRentangGajiAttribute($value)
    {
        return $this->safeJsonDecode($value);
    }

    /**
     * Get tahapan_seleksi as array
     */
    public function getTahapanSeleksiAttribute($value)
    {
        return $this->safeJsonDecode($value);
    }

    /**
     * Safe JSON decode helper
     */
    private function safeJsonDecode($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get formatted tanggal_mulai
     */
    public function getTanggalMulaiFormattedAttribute()
    {
        return $this->tanggal_mulai ? $this->tanggal_mulai->format('d M Y') : '-';
    }

    /**
     * Get formatted tanggal_selesai
     */
    public function getTanggalSelesaiFormattedAttribute()
    {
        return $this->tanggal_selesai ? $this->tanggal_selesai->format('d M Y') : '-';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'aktif' => 'success',
            'ditutup' => 'danger',
            'draft' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'aktif' => 'Aktif',
            'ditutup' => 'Ditutup',
            'draft' => 'Draft',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get company logo URL
     */
    public function getCompanyLogoUrlAttribute()
    {
        if ($this->company_logo) {
            return asset('storage/' . $this->company_logo);
        }
        
        // Default logo based on work unit or random
        return asset('build/images/companies/img-' . rand(1, 8) . '.png');
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->tanggal_selesai) {
            return null;
        }

        $now = now();
        $endDate = $this->tanggal_selesai;

        if ($now > $endDate) {
            return 0;
        }

        return $now->diffInDays($endDate);
    }

    /**
     * Get days remaining text
     */
    public function getDaysRemainingTextAttribute()
    {
        $days = $this->days_remaining;
        
        if (is_null($days)) {
            return 'No deadline';
        }

        if ($days <= 0) {
            return 'Closed';
        }

        if ($days == 1) {
            return '1 day remaining';
        }

        return $days . ' days remaining';
    }

    // ============== SCOPES ==============

    /**
     * Scope active jobs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif')
            ->where('tanggal_mulai', '<=', now())
            ->where('tanggal_selesai', '>=', now());
    }

    /**
     * Scope draft jobs
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope closed jobs
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'ditutup')
            ->orWhere('tanggal_selesai', '<', now());
    }

    /**
     * Scope jobs by work unit
     */
    public function scopeByWorkUnit($query, $workUnitId)
    {
        return $query->where('work_unit_id', $workUnitId);
    }

    /**
     * Scope jobs created by user
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope approved jobs
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')
            ->whereNotNull('approved_by');
    }

    /**
     * Scope pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->whereNull('approved_at')
            ->where('status', '!=', 'draft');
    }

    /**
     * Scope search by keyword
     */
    public function scopeSearch($query, $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function($q) use ($keyword) {
            $q->where('judul', 'LIKE', "%{$keyword}%")
              ->orWhere('posisi', 'LIKE', "%{$keyword}%")
              ->orWhere('kode_lowongan', 'LIKE', "%{$keyword}%")
              ->orWhere('deskripsi_pekerjaan', 'LIKE', "%{$keyword}%");
        });
    }

    /**
     * Scope filter by type
     */
    public function scopeOfType($query, $type)
    {
        if (empty($type)) {
            return $query;
        }

        return $query->where('jenis_pegawai', $type);
    }

    /**
     * Scope filter by status pegawai
     */
    public function scopeByStatusPegawai($query, $status)
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status_pegawai', $status);
    }

    /**
     * Scope with available slots
     */
    public function scopeWithAvailableSlots($query)
    {
        return $query->whereColumn('kuota', '>', 'kuota_terisi');
    }

    // ============== ATTRIBUTE HELPERS ==============

    /**
     * Check if job is active
     */
    public function isActive()
    {
        return $this->status === 'aktif' 
            && $this->tanggal_mulai <= now() 
            && $this->tanggal_selesai >= now();
    }

    /**
     * Check if job is draft
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if job is closed
     */
    public function isClosed()
    {
        return $this->status === 'ditutup' || $this->tanggal_selesai < now();
    }

    /**
     * Check if job is approved
     */
    public function isApproved()
    {
        return !is_null($this->approved_at) && !is_null($this->approved_by);
    }

    /**
     * Check if job has available slots
     */
    public function hasAvailableSlots()
    {
        return $this->kuota > $this->kuota_terisi;
    }

    /**
     * Get available slots count
     */
    public function getAvailableSlotsAttribute()
    {
        return max(0, $this->kuota - $this->kuota_terisi);
    }

    /**
     * Get filled percentage
     */
    public function getFilledPercentageAttribute()
    {
        if ($this->kuota <= 0) {
            return 0;
        }

        return round(($this->kuota_terisi / $this->kuota) * 100, 2);
    }

    // ============== BUSINESS LOGIC ==============

    /**
     * Approve the job
     */
    public function approve($userId)
    {
        $this->update([
            'approved_by' => $userId,
            'approved_at' => now(),
            'status' => 'aktif'
        ]);

        return $this;
    }

    /**
     * Reject the job
     */
    public function reject($reason = null)
    {
        $this->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null
        ]);

        // You can store rejection reason in a separate table if needed

        return $this;
    }

    /**
     * Close the job
     */
    public function close()
    {
        $this->update([
            'status' => 'ditutup'
        ]);

        return $this;
    }

    /**
     * Reopen the job
     */
    public function reopen()
    {
        if ($this->tanggal_selesai < now()) {
            // Extend the deadline if already passed
            $this->update([
                'tanggal_selesai' => now()->addDays(30),
                'status' => 'aktif'
            ]);
        } else {
            $this->update([
                'status' => 'aktif'
            ]);
        }

        return $this;
    }

    /**
     * Increment kuota_terisi
     */
    public function incrementFilledQuota($amount = 1)
    {
        $this->increment('kuota_terisi', $amount);
        
        // Auto close if quota is full
        if ($this->kuota_terisi >= $this->kuota) {
            $this->close();
        }

        return $this;
    }

    /**
     * Decrement kuota_terisi
     */
    public function decrementFilledQuota($amount = 1)
    {
        $this->decrement('kuota_terisi', max(0, $amount));
        
        // Auto activate if quota becomes available and status was closed due to full quota
        if ($this->status === 'ditutup' && $this->kuota_terisi < $this->kuota) {
            $this->reopen();
        }

        return $this;
    }

    /**
     * Get formatted requirements for display
     */
    public function getFormattedRequirements()
    {
        $requirements = [];

        // Pendidikan
        if (!empty($this->kualifikasi_pendidikan)) {
            $requirements[] = 'Pendidikan: ' . implode(', ', $this->kualifikasi_pendidikan);
        }

        // Pengalaman
        if (!empty($this->kualifikasi_pengalaman)) {
            $requirements[] = 'Pengalaman: ' . implode(', ', $this->kualifikasi_pengalaman);
        }

        // Kompetensi
        if (!empty($this->kompetensi_dibutuhkan)) {
            $requirements[] = 'Kompetensi: ' . implode(', ', $this->kompetensi_dibutuhkan);
        }

        return $requirements;
    }

    /**
     * Get formatted salary range
     */
    public function getFormattedSalaryAttribute()
    {
        if (empty($this->rentang_gaji)) {
            return 'Negotiable';
        }

        $gaji = $this->rentang_gaji;
        
        if (isset($gaji['min']) && isset($gaji['max'])) {
            return 'Rp ' . number_format($gaji['min'], 0, ',', '.') . 
                   ' - Rp ' . number_format($gaji['max'], 0, ',', '.');
        }

        if (isset($gaji['min'])) {
            return '≥ Rp ' . number_format($gaji['min'], 0, ',', '.');
        }

        if (isset($gaji['max'])) {
            return '≤ Rp ' . number_format($gaji['max'], 0, ',', '.');
        }

        return 'Negotiable';
    }

    /**
     * Get formatted selection stages
     */
    public function getFormattedSelectionStagesAttribute()
    {
        if (empty($this->tahapan_seleksi)) {
            return [];
        }

        $stages = [];
        foreach ($this->tahapan_seleksi as $index => $stage) {
            $stages[] = [
                'number' => $index + 1,
                'name' => $stage,
                'status' => 'pending' // You can implement actual status tracking
            ];
        }

        return $stages;
    }

    /**
     * Get applications grouped by status
     */
    public function getApplicationsByStatus()
    {
        return $this->applications()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    /**
     * Check if user has applied
     */
    public function hasUserApplied($userId)
    {
        return $this->applications()
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get application for specific user
     */
    public function getUserApplication($userId)
    {
        return $this->applications()
            ->where('user_id', $userId)
            ->first();
    }
}