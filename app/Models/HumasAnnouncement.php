<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class HumasAnnouncement extends Model
{
    use HasUuids;

    protected $table = 'humas_announcements';

    protected $fillable = [
        'school_id', 'target_type', 'dormitory_id', 'study_group_id',
        'title', 'slug', 'category', 'severity', 'channels', 'ack_required', 'recipient_group',
        'audience', 'visibility', 'needs_response', 'excerpt', 'content',
        'banner_path', 'attachment_path', 'status', 'is_pinned',
        'published_at', 'expires_at', 'activity_start_date', 'activity_end_date', 'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'ack_required' => 'boolean',
        'needs_response' => 'boolean',
        'channels' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'activity_start_date' => 'date',
        'activity_end_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(HumasAnnouncementImage::class)->orderBy('sort_order');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function acknowledgements()
    {
        return $this->hasMany(AnnouncementAcknowledgement::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    public function responses()
    {
        return $this->hasMany(HumasAnnouncementResponse::class, 'humas_announcement_id');
    }

    public function getVisibilityTextAttribute(): string
    {
        return match ($this->visibility) {
            'pengurus' => 'Pengurus Asrama',
            'umum' => 'Umum',
            default => 'Wali Santri',
        };
    }

    public function getCategoryTextAttribute(): string
    {
        return match ($this->category) {
            'pengumuman' => 'Pengumuman',
            'undangan' => 'Undangan',
            'laporan' => 'Laporan',
            'darurat' => 'Darurat',
            'broadcast' => 'Broadcast',
            default => ucfirst($this->category ?? ''),
        };
    }

    public function getBannerUrlAttribute(): ?string
    {
        $image = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();

        if ($image?->path) {
            return asset('storage/'.$image->path);
        }

        return $this->banner_path ? asset('storage/'.$this->banner_path) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            if ($model->banner_path) {
                Storage::disk('public')->delete($model->banner_path);
            }

            foreach ($model->images()->get() as $image) {
                Storage::disk('public')->delete($image->path);
            }

            Kaldik::withTrashed()
                ->where('source_type', self::class)
                ->where('source_id', $model->id)
                ->get()
                ->each->forceDelete();
        });

        static::creating(function ($model) {
            if (empty($model->target_type)) {
                $model->target_type = $model->school_id ? 'school' : 'global';
            }
            if (empty($model->recipient_group)) {
                $model->recipient_group = 'wali';
            }
            if (empty($model->channels)) {
                $model->channels = ['inapp', 'push'];
            }
            if (empty($model->severity)) {
                $model->severity = 'info';
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }
}
