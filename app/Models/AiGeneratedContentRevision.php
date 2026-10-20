<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiGeneratedContentRevision extends Model
{
    protected $table = 'ai_generated_content_revisions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'ai_generated_content_id',
        'content',
        'changed_by',
        'note',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(AiGeneratedContent::class, 'ai_generated_content_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
