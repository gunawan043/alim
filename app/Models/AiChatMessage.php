<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiChatMessage extends Model
{
    protected $table = 'ai_chat_messages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();

            if (empty($m->sequence) && $m->conversation_id) {
                $m->sequence = ((int) static::query()->where('conversation_id', $m->conversation_id)->max('sequence')) + 1;
            }
        });
    }

    protected $fillable = [
        'conversation_id', 'role', 'sequence', 'content', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
