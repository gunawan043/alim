<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CleaningReportItem extends Model
{
    protected $table = 'cleaning_report_items';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'report_id', 'task_id', 'result', 'condition', 'notes',
        'before_photo_path', 'before_photo_url', 'after_photo_path', 'after_photo_url',
    ];

    public const RESULT_OPTIONS = ['sudah', 'belum', 'tidak_berlaku'];

    public function report(): BelongsTo { return $this->belongsTo(CleaningReport::class, 'report_id'); }
    public function task(): BelongsTo { return $this->belongsTo(CleaningTask::class, 'task_id'); }
}
