<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerAnggota;
use Illuminate\Http\Request;

class KoordEkskulDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        $ekskulDiampu = Ekstrakurikuler::where('gtk_id', $user->id)
            ->count();
        if ($ekskulDiampu === 0) {
            $ekskulDiampu = Ekstrakurikuler::aktif()->count();
        }

        $totalAnggota = EkstrakurikulerAnggota::whereDate('tanggal_bergabung', '<=', now())
            ->where('status', EkstrakurikulerAnggota::STATUS_AKTIF)
            ->whereHas('ekstrakurikuler', fn ($q) => $q->where('gtk_id', $user->id))
            ->distinct('student_id')
            ->count();

        $hariIni = now()->format('l');
        $pertemuanHariIni = Ekstrakurikuler::aktif()
            ->where('hari', $hariIni)
            ->count();

        $acaraMendatang = Ekstrakurikuler::aktif()
            ->where('tanggal_selesai', '>=', now())
            ->count();

        $ekskulList = Ekstrakurikuler::aktif()
            ->with(['anggotaAktif', 'gtk'])
            ->limit(6)
            ->get();

        $kegiatanLog = Ekstrakurikuler::where('gtk_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('dashboard-pendidikan.tugas-tambahan.koordinator-ekskul', compact(
            'user', 'ekskulDiampu', 'totalAnggota',
            'pertemuanHariIni', 'acaraMendatang',
            'ekskulList', 'kegiatanLog'
        ));
    }
}
