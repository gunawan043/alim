<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\NilaiSumatif;
use App\Models\PelanggaranLog;
use App\Models\StudentAttendance;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use Illuminate\Http\Request;

class WaliKelasDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        // Cari kelas yang diampu (homeroom teacher)
        $kelas = StudyGroup::where('homeroom_teacher_id', $user->id)
            ->where('is_active', true)
            ->with(['gradeLevel', 'students'])
            ->first();

        $totalSantri = $kelas ? $kelas->students()->where('status', 'aktif')->count() : 0;

        $hadirToday = $kelas
            ? StudentAttendance::where('study_group_id', $kelas->id)
                ->where('attendance_date', $today)
                ->where('status', 'hadir')
                ->count()
            : 0;

        $hadirPercent = $totalSantri > 0 ? round(($hadirToday / $totalSantri) * 100) : 0;

        // Santri perlu perhatian — pelanggaran > threshold dalam 30 hari
        $perluPerhatian = $kelas
            ? PelanggaranLog::where('study_group_id', $kelas->id)
                ->where('tanggal', '>=', now()->subDays(30))
                ->select('student_id')
                ->groupBy('student_id')
                ->havingRaw('SUM(pl.poin) > 10', function ($q) {
                    $q->join('pelanggaran as pl', 'pl.id', '=', 'pelanggaran_logs.pelanggaran_id');
                })
                ->count()
            : 0;

        $santriList = $kelas ? $kelas->students()->where('status', 'aktif')->get() : collect();

        $absensiList = $kelas
            ? StudentAttendance::where('study_group_id', $kelas->id)
                ->where('attendance_date', $today)
                ->with('student')
                ->get()
            : collect();

        // Rata-rata nilai dari admin book wali kelas
        $adminBook = TeacherAdminBook::where('study_group_id', $kelas?->id)
            ->where('teacher_id', $user->id)
            ->first();

        $nilaiRata = $adminBook
            ? NilaiSumatif::where('admin_book_id', $adminBook->id)
                ->avg('nr_final') ?? 0
            : 0;

        return view('dashboard-pendidikan.tugas-tambahan.wali-kelas', compact(
            'user', 'totalSantri', 'hadirToday', 'hadirPercent',
            'perluPerhatian', 'santriList', 'absensiList', 'nilaiRata', 'kelas'
        ));
    }
}
