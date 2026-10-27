<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppFeedbackAttachment extends Model
{
    use HasUuids;

    protected $table = 'app_feedback_attachments';

    protected $fillable = [
        'app_feedback_report_id',
        'uploaded_by',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(AppFeedbackReport::class, 'app_feedback_report_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
