<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\DormitoryPermit;
use App\Models\Ekstrakurikuler;
use App\Models\PelanggaranLog;
use App\Models\Student;
use Illuminate\Http\Request;

class KoordKesiswaanDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $totalSantri = Student::where('status', 'aktif')->count();

        // Pelanggaran bulan ini — dari PelanggaranLog
        $pelanggaran = PelanggaranLog::where('tanggal', '>=', now()->startOfMonth())
            ->count();

        $ekskulAktif = Ekstrakurikuler::aktif()->count();

        // Izin keluar pending — menggunakan DormitoryPermit
        $izinPending = DormitoryPermit::where('status', 'pending')
            ->count();

        // Recent violations
        $recentViolations = PelanggaranLog::with(['student', 'dicatat_oleh'])
            ->where('tanggal', '>=', now()->subDays(7))
            ->orderByDesc('tanggal')
            ->limit(8)
            ->get();

        $ekskulList = Ekstrakurikuler::aktif()
            ->with(['gtk'])
            ->limit(6)
            ->get();

        return view('dashboard-pendidikan.tugas-tambahan.koordinator-kesiswaan', compact(
            'user', 'totalSantri', 'pelanggaran',
            'ekskulAktif', 'izinPending',
            'recentViolations', 'ekskulList'
        ));
    }
}
