<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelanggaranLog extends Model
{
    use HasUuids;

    protected $table = 'pelanggaran_log';

    protected $fillable = ['user_id', 'pelanggaran_id', 'tanggal', 'keterangan', 'dokumen_path', 'dicatat_oleh'];

    protected $casts = ['tanggal' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pelanggaran(): BelongsTo
    {
        return $this->belongsTo(Pelanggaran::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
