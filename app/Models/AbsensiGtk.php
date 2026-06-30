<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiGtk extends Model
{
    use HasUuids;
    protected $table = 'absensi_gtk';

    protected $fillable = [
        'gtk_id', 'tanggal', 'status', 'jam_masuk', 'jam_pulang',
        'terlambat_menit', 'pulang_awal_menit', 'keterangan',
        'lokasi_masuk', 'foto_masuk_path', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'string',
        'jam_pulang' => 'string',
        'terlambat_menit' => 'integer',
        'pulang_awal_menit' => 'integer',
    ];

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(GtkProfile::class, 'gtk_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
