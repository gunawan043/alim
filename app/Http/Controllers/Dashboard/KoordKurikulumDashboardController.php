<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\ExamSchedule;
use App\Models\Kaldik;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\Subject;
use App\Models\SubjectKktp;
use Illuminate\Http\Request;

class KoordKurikulumDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $totalMapel = Subject::count();

        // Progress silabus — rata-rata KKTP score per mapel
        $progressSilabus = SubjectKktp::where('academic_year_id', $academicYear?->id)
            ->avg('kktp_score') ?? 0;
        $progressSilabus = round($progressSilabus);

        $paketSoalPending = PaketSoal::where('status', 'draft')
            ->orWhere('status', 'review')
            ->count();

        $ujianMendatang = ExamSchedule::where('tanggal_ujian', '>=', now())
            ->where('tanggal_ujian', '<=', now()->addDays(14))
            ->count();

        $calendarEvents = Kaldik::where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        $soalReview = Soal::where('status', 'pending_review')
            ->with(['paketSoal'])
            ->limit(5)
            ->get();

        return view('dashboard-pendidikan.tugas-tambahan.koordinator-kurikulum', compact(
            'user', 'totalMapel', 'progressSilabus',
            'paketSoalPending', 'ujianMendatang',
            'calendarEvents', 'soalReview'
        ));
    }
}
