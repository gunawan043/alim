<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\JadwalKbm;
use App\Models\StudentAttendance;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use Illuminate\Http\Request;

class GuruDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        // Jadwal hari ini
        $jadwalHariIni = JadwalKbm::where('date', $today)
            ->where('teacher_id', $user->id)
            ->count();

        $kelasAktif = JadwalKbm::where('date', $today)
            ->where('teacher_id', $user->id)
            ->where('end_time', '>=', now()->format('H:i'))
            ->count();

        // Siswa absen di kelas yang diampu (via jadwal kbm)
        $siswaAbsen = StudentAttendance::where('attendance_date', $today)
            ->whereNotIn('status', ['hadir', 'sakit', 'izin'])
            ->whereHas('studyGroup', function ($q) use ($user, $today) {
                $q->whereHas('jadwalKbms', fn ($jq) => $jq->where('teacher_id', $user->id)->where('date', $today));
            })
            ->distinct('student_id')
            ->count();

        // Grading pending — admin book milik guru dengan nilai sumatif belum final
        $gradingPending = TeacherAdminBook::where('teacher_id', $user->id)
            ->whereHas('nilaiSumatifs', fn ($q) => $q->whereNull('nr_final'))
            ->count();

        $jadwalDetail = JadwalKbm::where('date', $today)
            ->where('teacher_id', $user->id)
            ->with(['studyGroup.gradeLevel', 'subject'])
            ->orderBy('start_time')
            ->get();

        $pendingGrading = TeacherAdminBook::where('teacher_id', $user->id)
            ->with(['studyGroup', 'subject'])
            ->has('nilaiSumatifs', fn ($q) => $q->whereNull('nr_final'))
            ->limit(5)
            ->get();

        $taskWaliKelas = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'wali kelas');
        });

        $taskGuruKoor = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'koordinator guru');
        });

        $taskEkskul = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'ekskul')
                || str_contains(strtolower($t->nama_tugas), 'ekstrakurikuler');
        });

        $taskLab = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'lab')
                || str_contains(strtolower($t->nama_tugas), 'laboratorium');
        });

        $taskSarpras = $additionalTasks->first(function ($t) {
            return str_contains(strtolower($t->nama_tugas), 'sarpras')
                || str_contains(strtolower($t->nama_tugas), 'prasarana');
        });

        // Fetch contextual data for wali kelas task
        $waliKelasInfo = null;
        if ($taskWaliKelas) {
            $kelas = StudyGroup::where('homeroom_teacher_id', $user->id)
                ->where('is_active', true)
                ->with(['gradeLevel', 'studentClassHistories.student'])
                ->first();

            if ($kelas) {
                $totalSantri = $kelas->studentClassHistories()->where('is_active', true)->count();
                $hadirToday = StudentAttendance::where('study_group_id', $kelas->id)
                    ->where('attendance_date', $today)
                    ->where('status', 'hadir')
                    ->count();
                $hadirPercent = $totalSantri > 0 ? round(($hadirToday / $totalSantri) * 100) : 0;

                $waliKelasInfo = [
                    'kelas' => $kelas,
                    'totalSantri' => $totalSantri,
                    'hadirToday' => $hadirToday,
                    'hadirPercent' => $hadirPercent,
                ];
            }
        }

        return view('dashboard-pendidikan.jabatan.guru', compact(
            'user', 'jadwalHariIni', 'kelasAktif',
            'siswaAbsen', 'gradingPending',
            'jadwalDetail', 'pendingGrading',
            'taskWaliKelas', 'taskGuruKoor', 'taskEkskul',
            'taskLab', 'taskSarpras', 'waliKelasInfo'
        ));
    }
}
