<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppFeedbackReport extends Model
{
    use HasUuids;

    protected $table = 'app_feedback_reports';

    public const CATEGORIES = [
        'bug' => 'Laporan Bug',
        'kritik' => 'Kritik',
        'saran' => 'Saran',
        'fitur' => 'Permintaan Fitur',
    ];

    public const PRIORITIES = [
        'rendah' => 'Rendah',
        'sedang' => 'Sedang',
        'tinggi' => 'Tinggi',
        'kritis' => 'Kritis',
    ];

    public const STATUSES = [
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'menunggu_informasi' => 'Menunggu Informasi',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    protected $fillable = [
        'school_id',
        'work_unit_id',
        'submitted_by',
        'category',
        'priority',
        'status',
        'title',
        'description',
        'process',
        'expected',
        'page_url',
        'division',
        'jabatan',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(AppFeedbackUpdate::class, 'app_feedback_report_id')->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AppFeedbackAttachment::class, 'app_feedback_report_id')->latest();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public function statusLabelFor(?string $status): string
    {
        return self::STATUSES[$status ?? ''] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    public function isResolved(): bool
    {
        return $this->status === 'selesai';
    }
}
