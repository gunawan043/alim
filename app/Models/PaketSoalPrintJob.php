<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Pekerjaan cetak/perbanyak paket soal oleh Tata Usaha.
 * TU hanya mengelola operasional produksi — tidak dapat mengubah isi soal.
 */
class PaketSoalPrintJob extends Model
{
    protected $table = 'paket_soal_print_jobs';

    protected $keyType = 'string';

    public $incrementing = false;

    const STATUS_ANTRI = 'antri';

    const STATUS_PROSES = 'proses';

    const STATUS_SELESAI = 'selesai';

    const STATUS_OPTIONS = [
        self::STATUS_ANTRI => 'Antri',
        self::STATUS_PROSES => 'Proses',
        self::STATUS_SELESAI => 'Selesai',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'paket_soal_id',
        'jumlah_cetak',
        'status',
        'tanggal_produksi',
        'petugas',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'jumlah_cetak' => 'integer',
        'tanggal_produksi' => 'date',
    ];

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class, 'paket_soal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
