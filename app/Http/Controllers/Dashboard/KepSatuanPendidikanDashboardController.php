<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\GtkRequest;
use App\Models\StructuralAssignment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudyGroup;
use App\Models\User;
use App\Models\ViolationPoint;
use Illuminate\Http\Request;

class KepSatuanPendidikanDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        $totalSantri = Student::where('status', 'aktif')->count();
        $totalGtk = User::where('is_active', true)->whereHas('employment')->count();
        $totalRombel = StudyGroup::where('is_active', true)->count();

        $kehadiranToday = StudentAttendance::where('attendance_date', $today)
            ->where('status', 'hadir')
            ->count();
        $totalHadirT = StudentAttendance::where('attendance_date', $today)->count();
        $kehadiranHariIni = $totalHadirT > 0 ? round(($kehadiranToday / $totalHadirT) * 100) : 0;

        $recentViolations = ViolationPoint::with(['student', 'recordedBy'])
            ->where('violation_date', '>=', now()->subDays(7))
            ->orderByDesc('violation_date')
            ->limit(5)
            ->get();

        $gtkExpiring = StructuralAssignment::where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now()->toDateString());
        })
            ->where('start_date', '<=', now()->toDateString())
            ->whereHas('position', fn ($posQ) => $posQ->where('role_id', function ($rq) {
                $rq->select('id')->from('roles')->where('name', 'Satuan Pendidikan');
            }))
            ->count();

        $pendingGtkRequests = GtkRequest::where('status', 'pending')->count();

        $taskKepsek = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'kepala satuan')
                || str_contains(strtolower($t->nama_tugas), 'kepala sekolah');
        });

        return view('dashboard-pendidikan.jabatan.kepala-satuan-pendidikan', compact(
            'user', 'totalSantri', 'totalGtk', 'totalRombel',
            'kehadiranHariIni', 'recentViolations',
            'gtkExpiring', 'pendingGtkRequests', 'taskKepsek'
        ));
    }
}
