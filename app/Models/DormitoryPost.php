<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DormitoryPost extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id',
        'title',
        'content',
        'category',
        'visibility',
        'needs_response',
        'is_pinned',
        'is_active',
        'attachment_path',
        'created_by',
    ];

    protected $casts = [
        'needs_response' => 'boolean',
        'is_pinned'      => 'boolean',
        'is_active'      => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'dormitory_post_responses',
            'post_id',
            'student_id'
        )->withPivot(['parent_name', 'response_type', 'message', 'created_at'])
         ->withTimestamps();
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getCategoryTextAttribute(): string
    {
        return match ($this->category) {
            'pengumuman' => 'Pengumuman',
            'undangan'   => 'Undangan',
            'laporan'    => 'Laporan',
            'darurat'    => 'Darurat',
            default      => ucfirst($this->category ?? ''),
        };
    }

    public function getVisibilityTextAttribute(): string
    {
        return match ($this->visibility) {
            'wali'     => 'Wali Santri',
            'pengurus' => 'Pengurus Asrama',
            'umum'     => 'Umum',
            default    => ucfirst($this->visibility ?? ''),
        };
    }

    public function getResponseRateAttribute(): float
    {
        if (!$this->needs_response) return 0;
        // Placeholder: calculate from DormitoryPostResponse
        return 0;
    }
}
