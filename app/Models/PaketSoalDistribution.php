<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Distribusi paket soal final ke penerima internal
 * (Tata Usaha, Waka, Kurikulum, Koordinator, KSP).
 */
class PaketSoalDistribution extends Model
{
    protected $table = 'paket_soal_distributions';

    protected $keyType = 'string';

    public $incrementing = false;

    const STATUS_SENT = 'sent';

    const STATUS_RECEIVED = 'received';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'paket_soal_id',
        'recipient_user_id',
        'recipient_role',
        'recipient_name',
        'status',
        'note',
        'distributed_at',
        'distributed_by',
    ];

    protected $casts = [
        'distributed_at' => 'datetime',
    ];

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class, 'paket_soal_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}
