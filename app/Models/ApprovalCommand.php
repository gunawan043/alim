<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Idempotency and audit record for external approval commands.
 */
class ApprovalCommand extends Model
{
    protected $table = 'approval_commands';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'command_id',
        'idempotency_key',
        'api_version',
        'permit_id',
        'actor_user_id',
        'actor_external_identity',
        'decision',
        'source',
        'external_message_id',
        'message_text',
        'payload_hash',
        'response_code',
        'response_body',
        'processed_at',
    ];

    protected $casts = [
        'id' => 'string',
        'permit_id' => 'string',
        'actor_user_id' => 'string',
        'response_body' => 'array',
        'processed_at' => 'datetime',
    ];

    public function permit(): BelongsTo
    {
        return $this->belongsTo(DormitoryPermit::class, 'permit_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
