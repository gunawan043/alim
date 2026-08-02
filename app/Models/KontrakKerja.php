<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KontrakKerja extends Model
{
    use HasUuids;
    use LogsDeletion;

    protected $table = 'kontrak_kerja';

    protected $fillable = [
        'gtk_uuid', 'school_id', 'nomor_kontrak', 'jenis', 'tanggal_mulai', 'tanggal_selesai',
        'durasi_bulan', 'jabatan', 'unit_kerja', 'ruanglingkup', 'ketentuan_pkwt',
        'lokasi_kerja', 'gaji_pokok', 'tunjangan_tetap', 'tunjangan_tidak_tetap',
        'status', 'tanggal_berakhir', 'dokumen_path', 'nama_penanda_tangan',
        'jabatan_penanda_tangan', 'catatan', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'tanggal_berakhir' => 'date',
        'gaji_pokok' => 'decimal:2', 'tunjangan_tetap' => 'decimal:2', 'tunjangan_tidak_tetap' => 'decimal:2',
    ];

    public function gtk()
    {
        return $this->belongsTo(GtkProfile::class, 'gtk_uuid', 'uuid');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function getSisaHariAttribute(): int
    {
        if ($this->tanggal_selesai < now()) {
            return 0;
        }

        return now()->diffInDays($this->tanggal_selesai);
    }

    public function scopeAktif($q)
    {
        return $q->where('status', 'aktif');
    }

    public function scopeExpiring($q, int $days = 90)
    {
        return $q->where('status', 'aktif')
            ->whereDate('tanggal_selesai', '>=', now())
            ->whereDate('tanggal_selesai', '<=', now()->addDays($days));
    }
}
