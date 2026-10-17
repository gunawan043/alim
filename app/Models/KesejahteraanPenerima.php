<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KesejahteraanPenerima extends Model
{
    use HasUuids;

    protected $table = 'kesejahteraan_penerima';

    protected $fillable = [
        'kesejahteraan_id', 'user_id', 'nilai', 'tanggal_mulai', 'tanggal_selesai',
        'status', 'catatan', 'approved_by', 'approved_at',
    ];

    protected $casts = ['nilai' => 'decimal:2', 'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'approved_at' => 'datetime'];

    public function kesejahteraan(): BelongsTo
    {
        return $this->belongsTo(Kesejahteraan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
