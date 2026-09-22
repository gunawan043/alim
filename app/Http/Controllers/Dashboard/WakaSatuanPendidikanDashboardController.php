<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\AbsensiGtk;
use App\Models\GtkRequest;
use App\Models\JadwalKbm;
use App\Models\StructuralAssignment;
use App\Models\StudyGroup;
use Illuminate\Http\Request;

class WakaSatuanPendidikanDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        $totalKelas = StudyGroup::where('is_active', true)->count();

        $guruAbsen = AbsensiGtk::where('tanggal', $today)
            ->where('status', 'absen')
            ->distinct('gtk_id')
            ->count();

        $pengajuanPending = GtkRequest::where('status', 'pending')->count();

        $tugasPending = StructuralAssignment::where('status', 'pending')->count();

        $piketList = StudyGroup::where('is_active', true)
            ->with(['homeroomTeacher', 'gradeLevel'])
            ->limit(10)
            ->get();

        $jadwalHariIni = JadwalKbm::where('date', $today)
            ->with(['studyGroup.gradeLevel', 'teacher'])
            ->limit(15)
            ->get();

        $taskWaka = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'wakil kepala')
                || str_contains(strtolower($t->nama_tugas), 'waka');
        });

        return view('dashboard-pendidikan.jabatan.wakil-kepala', compact(
            'user', 'totalKelas', 'guruAbsen',
            'pengajuanPending', 'tugasPending',
            'piketList', 'jadwalHariIni', 'taskWaka'
        ));
    }
}
