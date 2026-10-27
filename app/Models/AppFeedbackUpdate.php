<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppFeedbackUpdate extends Model
{
    use HasUuids;

    protected $table = 'app_feedback_updates';

    protected $fillable = [
        'app_feedback_report_id',
        'user_id',
        'type',
        'status_from',
        'status_to',
        'message',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(AppFeedbackReport::class, 'app_feedback_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
