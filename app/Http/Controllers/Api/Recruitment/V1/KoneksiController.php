<?php

namespace App\Http\Controllers\Api\Recruitment\V1;

use App\Http\Controllers\Api\Shared\IntegrationController;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Tes koneksi + ringkasan data referensi Personalia untuk aplikasi recruitment.
 *
 * GET /api/recruitment/v1/ping
 */
class KoneksiController extends IntegrationController
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
            'jumlah_sekolah' => School::active()->count(),
            'jumlah_role' => Role::query()->count(),
            'endpoint_tersedia' => [
                'pegawai',
                'satuan-kerja',
                'jabatan',
                'jenis-gtk',
                'sekolah',
                'role',
            ],
        ], 'Koneksi ke ALIM berhasil.');
    }
}
