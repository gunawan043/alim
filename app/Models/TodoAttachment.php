<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TodoAttachment extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'todo_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        static::deleted(function ($model) {
            if ($model->file_path && Storage::exists($model->file_path)) {
                Storage::delete($model->file_path);
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ─── Accessors ───────────────────────────────────────────────

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    public function getIconAttribute(): string
    {
        $type = $this->file_type ?? '';
        if (str_starts_with($type, 'image/')) {
            return 'ri-image-line';
        }
        if (str_contains($type, 'pdf')) {
            return 'ri-file-pdf-2-line';
        }
        if (str_contains($type, 'word') || str_contains($type, 'document')) {
            return 'ri-file-word-2-line';
        }
        if (str_contains($type, 'sheet') || str_contains($type, 'excel')) {
            return 'ri-file-excel-2-line';
        }
        return 'ri-file-3-line';
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }
}
