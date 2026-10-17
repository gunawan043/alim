<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(string $userId)
    {
        $stats = $this->buildStats();

        return view('recruitment.reports.index', compact('userId', 'stats'));
    }

    public function dashboard(string $userId)
    {
        $stats = $this->buildStats();

        return view('recruitment.reports.dashboard', compact('userId', 'stats'));
    }

    public function hiringFunnel(string $userId)
    {
        $stats = $this->buildStats();
        $funnelStages = $this->getFunnelStages();

        return view('recruitment.reports.hiring-funnel', compact('userId', 'stats', 'funnelStages'));
    }

    public function timeToHire(string $userId)
    {
        $stats = $this->buildStats();

        return view('recruitment.reports.time-to-hire', compact('userId', 'stats'));
    }

    public function sourceEffectiveness(string $userId)
    {
        $stats = $this->buildStats();

        return view('recruitment.reports.source-effectiveness', compact('userId', 'stats'));
    }

    public function export(Request $request, string $userId, string $type)
    {
        abort_unless(in_array($type, ['excel', 'pdf']), 404);
        $stats = $this->buildStats();

        return view('recruitment.reports.export', compact('userId', 'stats', 'type'));
    }

    public function schedule(Request $request, string $userId)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
        ]);

        setting_put("recruitment.report_schedule.{$userId}", $validated);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal laporan berhasil disimpan.',
        ]);
    }

    private function getFunnelStages(): array
    {
        $stages = [
            ['key' => 'total',           'label' => 'Total Pelamar',          'count' => RecruitmentApplication::count()],
            ['key' => 'seleksi_adm',     'label' => 'Seleksi Administrasi',   'count' => RecruitmentApplication::whereIn('status', ['seleksi_administrasi', 'lolos_administrasi', 'tidak_lolos_administrasi'])->count()],
            ['key' => 'lolos_adm',       'label' => 'Lolos Administrasi',     'count' => RecruitmentApplication::where('status', 'lolos_administrasi')->count()],
            ['key' => 'tes',             'label' => 'Tes Tertulis',           'count' => RecruitmentApplication::whereIn('status', ['tes_tertulis', 'lolos_tes', 'tidak_lolos_tes'])->count()],
            ['key' => 'lolos_tes',       'label' => 'Lolos Tes',              'count' => RecruitmentApplication::where('status', 'lolos_tes')->count()],
            ['key' => 'wawancara',       'label' => 'Wawancara',              'count' => RecruitmentApplication::whereIn('status', ['wawancara_hr', 'wawancara_user', 'lolos_wawancara_hr', 'lolos_wawancara_user', 'tidak_lolos_wawancara'])->count()],
            ['key' => 'diterima',        'label' => 'Diterima',               'count' => RecruitmentApplication::where('status', 'diterima')->count()],
        ];

        foreach ($stages as $i => &$stage) {
            $prevCount = $i > 0 ? $stages[$i - 1]['count'] : $stage['count'];
            $stage['conversion'] = $prevCount > 0 ? round($stage['count'] / $prevCount * 100, 1) : 0;
        }

        return $stages;
    }

    private function buildStats(): array
    {
        $totalJobs = RecruitmentJob::count();
        $activeJobs = RecruitmentJob::where('status', 'aktif')->count();

        $totalApplications = RecruitmentApplication::count();
        $hiredCount = RecruitmentApplication::where('status', 'diterima')->count();

        // Hiring funnel
        $funnel = $this->getFunnelStages();

        // Time to hire
        $acceptedStages = RecruitmentApplication::where('status', 'diterima')
            ->whereNotNull('selesai_at')
            ->get();

        $days = $acceptedStages->map(function ($app) {
            return Carbon::parse($app->created_at)->diffInDays(Carbon::parse($app->selesai_at));
        });

        $timeToHire = [
            'average_days' => $days->count() > 0 ? round($days->avg(), 1) : 0,
            'median_days' => $days->count() > 0 ? (float) round($days->sort()->values()->get((int) ceil($days->count() / 2) - 1) ?? 0) : 0,
            'min_days' => $days->count() > 0 ? $days->min() : 0,
            'max_days' => $days->count() > 0 ? $days->max() : 0,
            'distribution' => [
                '< 7' => $days->filter(fn ($d) => $d < 7)->count(),
                '7-14' => $days->filter(fn ($d) => $d >= 7 && $d <= 14)->count(),
                '15-30' => $days->filter(fn ($d) => $d > 14 && $d <= 30)->count(),
                '31-60' => $days->filter(fn ($d) => $d > 30 && $d <= 60)->count(),
                '> 60' => $days->filter(fn ($d) => $d > 60)->count(),
            ],
        ];

        // Growth
        $appGrowth = $this->calcGrowth(RecruitmentApplication::class, 'created_at');
        $hiredGrowth = $this->calcGrowth(RecruitmentApplication::class, 'updated_at', 'diterima');

        // Job performance
        $jobs = RecruitmentJob::with('workUnit')
            ->withCount(['applications', 'applications as hired_count' => fn ($q) => $q->where('status', 'diterima')])
            ->orderBy('applications_count', 'desc')
            ->limit(10)
            ->get();

        $jobPerformance = $jobs->map(fn ($job) => [
            'kode' => $job->kode_lowongan,
            'judul' => $job->judul,
            'unit' => $job->workUnit?->name ?? '-',
            'kuota' => $job->kuota,
            'terisi' => $job->kuota_terisi ?? 0,
            'total_pelamar' => $job->applications_count,
            'total_hired' => $job->hired_count,
            'konversi' => $job->applications_count > 0 ? round($job->hired_count / $job->applications_count * 100, 1) : 0,
            'sisa_kuota' => max(0, $job->kuota - ($job->kuota_terisi ?? 0)),
        ])->toArray();

        // Demographics
        $genderStats = DB::table('recruitment_applications')
            ->join('recruitment_profiles', 'recruitment_applications.recruitment_profile_id', '=', 'recruitment_profiles.id')
            ->selectRaw("SUM(CASE WHEN recruitment_profiles.jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki_laki,
                         SUM(CASE WHEN recruitment_profiles.jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan")
            ->first();

        $ageStats = DB::table('recruitment_applications')
            ->join('recruitment_profiles', 'recruitment_applications.recruitment_profile_id', '=', 'recruitment_profiles.id')
            ->whereNotNull('recruitment_profiles.tanggal_lahir')
            ->selectRaw('
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) < 25 THEN 1 ELSE 0 END) as kurang_25,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) BETWEEN 25 AND 30 THEN 1 ELSE 0 END) as range_25_30,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) BETWEEN 31 AND 35 THEN 1 ELSE 0 END) as range_31_35,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) BETWEEN 36 AND 40 THEN 1 ELSE 0 END) as range_36_40,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) > 40 THEN 1 ELSE 0 END) as lebih_40
            ')->first();

        $eduStats = DB::table('recruitment_educations')
            ->selectRaw('jenjang, COUNT(*) as total')
            ->whereNotNull('jenjang')
            ->groupBy('jenjang')
            ->pluck('total', 'jenjang')
            ->toArray();

        // Trends - 12 months
        $applicationsTrend = [];
        $hiresTrend = [];
        $months = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $key = $month->format('Y-m');
            $months[] = $month->format('M Y');

            $applicationsTrend[] = (int) RecruitmentApplication::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$key])->count();

            $hiresTrend[] = (int) RecruitmentApplication::where('status', 'diterima')
                ->whereRaw("DATE_FORMAT(COALESCE(selesai_at, updated_at), '%Y-%m') = ?", [$key])
                ->count();
        }

        return [
            'total_jobs' => $totalJobs,
            'active_jobs' => $activeJobs,
            'total_applications' => $totalApplications,
            'application_growth' => $appGrowth,
            'hired_count' => $hiredCount,
            'hired_growth' => $hiredGrowth,
            'application_rate' => $totalApplications > 0 ? round($hiredCount / $totalApplications * 100, 1) : 0,
            'time_to_hire' => $timeToHire,
            'hiring_funnel' => $funnel,
            'job_performance' => $jobPerformance,
            'applicant_demographics' => [
                'gender' => [
                    'L' => (int) ($genderStats->laki_laki ?? 0),
                    'P' => (int) ($genderStats->perempuan ?? 0),
                ],
                'age_groups' => [
                    '< 25' => (int) ($ageStats->kurang_25 ?? 0),
                    '25-30' => (int) ($ageStats->range_25_30 ?? 0),
                    '31-35' => (int) ($ageStats->range_31_35 ?? 0),
                    '36-40' => (int) ($ageStats->range_36_40 ?? 0),
                    '> 40' => (int) ($ageStats->lebih_40 ?? 0),
                ],
                'education' => $eduStats,
            ],
            'trends' => [
                'months' => $months,
                'applications' => $applicationsTrend,
                'hires' => $hiresTrend,
            ],
        ];
    }

    private function calcGrowth(string $model, string $dateCol, ?string $statusFilter = null): int
    {
        $thisMonth = now()->copy()->startOfMonth();
        $lastMonth = now()->copy()->subMonth()->startOfMonth();

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
}
