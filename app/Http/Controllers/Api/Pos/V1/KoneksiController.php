<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Models\Student;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Tes koneksi untuk halaman "Integrasi Alim" di aplikasi POS.
 *
 * Ringan, tanpa filter, dan membalas ringkasan jumlah data sehingga tombol
 * "Tes Koneksi" di POS bisa memastikan kredensial benar sekaligus server
 * siap dipakai sinkronisasi.
 *
 * GET /api/pos/v1/ping
 */
class KoneksiController extends PosApiController
{
    public function ping(): JsonResponse
    {
        return $this->ok([
            'app' => config('app.name', 'ALIM'),
            'server_time' => now()->toIso8601String(),
            'jumlah_pegawai' => User::query()
                ->where('is_active', true)
                ->where(function (Builder $q) {
                    $q->whereHas('employment')
                        ->orWhereHas('gtkWorkUnits')
                        ->orWhereHas('gtkProfile');
                })
                ->count(),
            'jumlah_satuan_kerja' => WorkUnit::active()->count(),
            'jumlah_santri' => Student::query()->where('status', 'active')->count(),
            'endpoint_tersedia' => [
                'satuan-kerja',
                'kepegawaian',
                'divisi',
                'jabatan',
                'jenis-gtk',
                'sekolah',
                'santri',
            ],
        ], 'Koneksi ke ALIM berhasil.');
    }
}
