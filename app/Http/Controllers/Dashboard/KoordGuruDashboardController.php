<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\AbsensiGtk;
use App\Models\JadwalKbm;
use App\Models\TeacherClassAttendance;
use App\Models\User;
use Illuminate\Http\Request;

class KoordGuruDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        $koorTask = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'koordinator guru');
        });

        $totalGuruKoor = $koorTask
            ? User::whereHas('employment', fn ($q) => $q->where('school_id', $schoolId))
                ->whereHas('positions', fn ($q) => $q->whereHas('jenisGtk', fn ($jg) => $jg->where('nama', 'Pendidik / Guru')))
                ->count()
            : 0;

        $guruAbsen = AbsensiGtk::where('tanggal', $today)
            ->where('status', 'absen')
            ->distinct('gtk_id')
            ->count();

        $jadwalBerlangsung = JadwalKbm::where('date', $today)
            ->where('end_time', '>=', now()->format('H:i'))
            ->count();

        $kehadiranBulanan = AbsensiGtk::where('tanggal', '>=', now()->startOfMonth())
            ->where('status', 'hadir')
            ->count();
        $totalAbsensiBulan = AbsensiGtk::where('tanggal', '>=', now()->startOfMonth())->count();
        $kehadiranPct = $totalAbsensiBulan > 0 ? round(($kehadiranBulanan / $totalAbsensiBulan) * 100) : 0;

        $substitusi = TeacherClassAttendance::whereDate('attendance_date', $today)
            ->where('is_substituted', true)
            ->with(['teacher'])
            ->limit(5)
            ->get();

        return view('dashboard-pendidikan.tugas-tambahan.koordinator-guru', compact(
            'user', 'totalGuruKoor', 'guruAbsen',
            'jadwalBerlangsung', 'kehadiranPct',
            'substitusi'
        ));
    }
}
