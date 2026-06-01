<?php

namespace App\Services;

use App\Models\Kesejahteraan;
use App\Models\KesejahteraanKlaim;
use App\Models\KesejahteraanPenerima;
use Illuminate\Support\Facades\DB;

class KesejahteraanService
{
    public function prosesKlaim(string $klaimId, string $status, ?float $nilaiDisetujui = null, ?string $catatanAdmin = null, ?string $diprosesOleh = null): KesejahteraanKlaim
    {
        $klaim = KesejahteraanKlaim::findOrFail($klaimId);
        $update = ['status' => $status, 'catatan_admin' => $catatanAdmin, 'diproses_oleh' => $diprosesOleh, 'diproses_at' => now()];
        if ($nilaiDisetujui !== null) $update['nilai_disetujui'] = $nilaiDisetujui;
        if (in_array($status, ['disetujui', 'dibayar'])) $update['nilai_disetujui'] = $nilaiDisetujui ?? $klaim->nilai_diminta;
        $klaim->update($update);
        return $klaim->fresh();
    }

    public function aktifkanPenerima(string $penerimaId, ?string $approvedBy = null): KesejahteraanPenerima
    {
        $p = KesejahteraanPenerima::findOrFail($penerimaId);
        $p->update(['status' => 'aktif', 'approved_by' => $approvedBy, 'approved_at' => now()]);
        return $p->fresh();
    }

    public function generateNomorKlaim(): string
    {
        $prefix = 'KLM-' . date('Ym') . '-';
        $last = KesejahteraanKlaim::where('nomor_klaim', 'like', $prefix . '%')
            ->orderBy('nomor_klaim', 'desc')->first();
        $num = $last ? (int) substr($last->nomor_klaim, -4) + 1 : 1;
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}