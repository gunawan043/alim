<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationStage;
use App\Models\RecruitmentJob;
use Illuminate\Support\Facades\DB;

class AtsDashboardController extends Controller
{
    public function index(string $userId)
    {
        $now = now();

        // ── Statistik Utama ──────────────────────────────────────────
        $stats = [
            'total_jobs' => RecruitmentJob::count(),
            'active_jobs' => RecruitmentJob::where('status', 'aktif')->count(),
            'draft_jobs' => RecruitmentJob::where('status', 'draft')->count(),
            'closed_jobs' => RecruitmentJob::whereIn('status', ['ditutup', 'dibatalkan'])->count(),
            'total_applications' => RecruitmentApplication::count(),
            'menunggu' => RecruitmentApplication::where('status', 'menunggu_seleksi')->count(),
            'seleksi_adm' => RecruitmentApplication::whereIn('status', ['seleksi_administrasi', 'lolos_administrasi', 'tidak_lolos_administrasi'])->count(),
            'diterima' => RecruitmentApplication::where('status', 'diterima')->count(),
            'ditolak' => RecruitmentApplication::where('status', 'ditolak')->count(),
            'dalam_proses' => RecruitmentApplication::whereNotIn('status', ['diterima', 'ditolak', 'mengundurkan_diri', 'blacklist'])->count(),
        ];

        $stats['app_growth'] = $this->calcGrowth(RecruitmentApplication::class, 'created_at');
        $stats['hired_growth'] = $this->calcGrowth(RecruitmentApplication::class, 'updated_at', 'diterima');

        // ── Hiring Funnel ───────────────────────────────────────────
        $total = RecruitmentApplication::count();
        $funnel = [
            'total' => ['count' => $total,                        'label' => 'Total Pelamar'],
            'seleksi_adm' => ['count' => RecruitmentApplication::whereIn('status', ['seleksi_administrasi'])->count(), 'label' => 'Seleksi Adm'],
            'lolos_adm' => ['count' => RecruitmentApplication::where('status', 'lolos_administrasi')->count(), 'label' => 'Lolos Adm'],
            'tes' => ['count' => RecruitmentApplication::whereIn('status', ['tes_tertulis', 'lolos_tes', 'tidak_lolos_tes'])->count(), 'label' => 'Tes Tertulis'],
            'lolos_tes' => ['count' => RecruitmentApplication::where('status', 'lolos_tes')->count(), 'label' => 'Lolos Tes'],
            'wawancara' => ['count' => RecruitmentApplication::whereIn('status', ['wawancara_hr', 'wawancara_user', 'lolos_wawancara_hr', 'lolos_wawancara_user', 'tidak_lolos_wawancara'])->count(), 'label' => 'Wawancara'],
            'diterima' => ['count' => $stats['diterima'],            'label' => 'Diterima'],
        ];

        // ── Chart Data (12 bulan terakhir) ─────────────────────────
        $labels = [];
        $applications = [];
        $hired = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');

            $applications[] = (int) RecruitmentApplication::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$key])->count();

            $hiredKey = $month->copy()->firstOfMonth()->format('Y-m');
            $hired[] = (int) RecruitmentApplication::where('status', 'diterima')
                ->whereRaw("DATE_FORMAT(COALESCE(selesai_at, updated_at), '%Y-%m') = ?", [$hiredKey])
                ->count();
        }

        $chartData = [
            'labels' => $labels,
            'applications' => $applications,
            'hired' => $hired,
        ];

        // ── Lowongan Akan Ditutup (7 hari ke depan) ─────────────────
        $expiringJobs = RecruitmentJob::withCount('applications')
            ->where('status', 'aktif')
            ->whereBetween('tanggal_selesai', [$now, $now->copy()->addDays(7)])
            ->orderBy('tanggal_selesai')
            ->limit(5)
            ->get();

        // ── Lowongan Baru (30 hari terakhir) ─────────────────────────
        $lowonganBaru = RecruitmentJob::where('created_at', '>=', $now->copy()->subDays(30))->count();

        // ── Top Lowongan ─────────────────────────────────────────────
        $topJobs = RecruitmentJob::withCount([
            'applications',
            'applications as accepted_count' => fn ($q) => $q->where('status', 'diterima'),
        ])
            ->where('status', 'aktif')
            ->orderBy('applications_count', 'desc')
            ->limit(5)
            ->get();

        // ── Ranking Kandidat (nilai_akhir DESC) ──────────────────────
        $topCandidates = RecruitmentApplication::with(['recruitmentProfile.user', 'recruitmentJob'])
            ->whereNotNull('nilai_akhir')
            ->whereNotIn('status', ['ditolak', 'blacklist'])
            ->orderByDesc('nilai_akhir')
            ->limit(5)
            ->get();

        // ── Reminder Interview (7 hari ke depan) ──────────────────────
        $interviewReminders = RecruitmentApplicationStage::with([
            'recruitmentApplication.recruitmentProfile.user',
            'recruitmentApplication.recruitmentJob',
        ])
            ->whereIn('status', ['menunggu', 'sedang_berlangsung'])
            ->whereNotNull('jadwal_mulai')
            ->whereBetween('jadwal_mulai', [$now, $now->copy()->addDays(7)])
            ->orderBy('jadwal_mulai')
            ->limit(6)
            ->get();

        // ── Aktivitas Terbaru ─────────────────────────────────────────
        $recentActivities = $this->getRecentActivities();

        // ── Konversi Rate ─────────────────────────────────────────────
        $konversiRate = $stats['total_applications'] > 0
            ? round($stats['diterima'] / $stats['total_applications'] * 100, 1)
            : 0;

        // ── Demografi ─────────────────────────────────────────────────
        $demographics = DB::table('recruitment_applications')
            ->join('recruitment_profiles', 'recruitment_applications.recruitment_profile_id', '=', 'recruitment_profiles.id')
            ->selectRaw("
                SUM(CASE WHEN recruitment_profiles.jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki_laki,
                SUM(CASE WHEN recruitment_profiles.jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan
            ")->first();

        return view('recruitment.dashboard.index', compact(
            'userId', 'stats', 'funnel', 'chartData',
            'expiringJobs', 'topJobs', 'topCandidates',
            'interviewReminders', 'recentActivities',
            'konversiRate', 'demographics', 'lowonganBaru'
        ));
    }

    private function calcGrowth(string $model, string $dateCol, ?string $statusFilter = null): int
    {
        $thisMonth = (clone now())->startOfMonth();
        $lastMonth = (clone now())->subMonth()->startOfMonth();

        $q = $model::where($dateCol, '>=', $thisMonth);
        if ($statusFilter) {
            $q->where('status', $statusFilter);
        }
        $current = $q->count();

        $q = $model::whereBetween($dateCol, [$lastMonth, $thisMonth->copy()->subSecond()]);
        if ($statusFilter) {
            $q->where('status', $statusFilter);
        }
        $previous = $q->count();

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function getRecentActivities(): \Illuminate\Support\Collection
    {
        try {
            $logModel = app(config('activitylog.package', 'Spatie\Activitylog\Models\Activity'));
            if (! class_exists($logModel)) {
                return collect();
            }

            return $logModel::with('subject', 'causer')
                ->where('log_name', 'recruitment')
                ->orWhere('description', 'like', '%lamaran%')
                ->orWhere('description', 'like', '%rekrutmen%')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }
}
