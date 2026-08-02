<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasUuids;

    protected $table = 'payroll';

    protected $fillable = [
        'gtk_id', 'bulan', 'tahun',
        'gaji_pokok', 'total_tunjangan', 'total_potongan', 'gaji_bersih',
        'detail_tunjangan', 'detail_potongan',
        'status', 'tanggal_bayar', 'catatan', 'dibuat_oleh',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'gaji_pokok' => 'decimal:2',
        'total_tunjangan' => 'decimal:2',
        'total_potongan' => 'decimal:2',
        'gaji_bersih' => 'decimal:2',
        'detail_tunjangan' => 'array',
        'detail_potongan' => 'array',
        'tanggal_bayar' => 'date',
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
