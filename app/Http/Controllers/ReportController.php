<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentJob;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $stats = $this->buildStats();
        return view('recruitment.reports.index', compact('stats'));
    }

    public function dashboard()
    {
        return view('recruitment.reports.dashboard');
    }

    public function hiringFunnel()
    {
        return view('recruitment.reports.hiring-funnel');
    }

    public function timeToHire()
    {
        return view('recruitment.reports.time-to-hire');
    }

    public function sourceEffectiveness()
    {
        return view('recruitment.reports.source-effectiveness');
    }

    public function export(string $type)
    {
        return response()->json(['message' => 'Export not implemented', 'type' => $type]);
    }

    public function schedule(Request $request)
    {
        return response()->json(['message' => 'Schedule not implemented']);
    }

    private function buildStats(): array
    {
        $totalJobs = RecruitmentJob::count();
        $activeJobs = RecruitmentJob::where('status', 'aktif')->count();

        $totalApplications = RecruitmentApplication::count();
        $hiredCount = RecruitmentApplication::where('status', 'hired')->count();

        // Hiring funnel by pipeline stages
        $hiringFunnel = [];
        $stageMap = [
            'administrasi_lolos' => 'Administrasi',
            'tes_lolos'          => 'Tes Tertulis',
            'wawancara_lolos'    => ['Wawancara HR', 'Wawancara User'],
        ];
        foreach ($stageMap as $key => $names) {
            $names = (array) $names;
            $totalCount = 0;
            foreach ($names as $name) {
                $pipelineStage = DB::table('recruitment_pipeline_stages')
                    ->where('nama_tahapan', $name)->first();
                if ($pipelineStage) {
                    $totalCount += DB::table('recruitment_application_stages')
                        ->where('recruitment_pipeline_stage_id', $pipelineStage->id)->count();
                }
            }
            $hiringFunnel[$key] = ['count' => $totalCount, 'label' => implode('/', $names)];
        }
        $hiringFunnel['applications'] = ['count' => $totalApplications, 'label' => 'Total Pelamar'];
        $hiringFunnel['hired']         = ['count' => $hiredCount,        'label' => 'Diterima'];

        // Time to hire
        $acceptedStages = RecruitmentApplicationStage::with('recruitmentPipelineStage')
            ->where('status', 'lolos')
            ->whereNotNull('selesai_at')->get();

        $days = $acceptedStages->map(fn($s) => $s->created_at->diffInDays($s->selesai_at));

        $timeToHire = [
            'average_days'  => $days->count() > 0 ? round($days->avg()) : 0,
            'median_days'   => $days->count() > 0
                ? round($days->sort()->values()->get((int) ceil($days->count() / 2) - 1) ?? 0) : 0,
            'min_days'     => $days->count() > 0 ? $days->min() : 0,
            'max_days'     => $days->count() > 0 ? $days->max() : 0,
            'distribution' => [
                '< 7'   => $days->filter(fn($d) => $d < 7)->count(),
                '7-14'  => $days->filter(fn($d) => $d >= 7  && $d <= 14)->count(),
                '15-30' => $days->filter(fn($d) => $d > 14  && $d <= 30)->count(),
                '31-60' => $days->filter(fn($d) => $d > 30  && $d <= 60)->count(),
                '> 60'  => $days->filter(fn($d) => $d > 60)->count(),
            ],
        ];

        // Growth
        $thisMonth  = RecruitmentApplication::whereMonth('created_at', now()->month)->count();
        $lastMonth  = RecruitmentApplication::whereMonth('created_at', now()->subMonth()->month)->count();
        $appGrowth  = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100) : ($thisMonth > 0 ? 100 : 0);

        $hiredThisMonth  = RecruitmentApplication::where('status', 'hired')->whereMonth('updated_at', now()->month)->count();
        $hiredLastMonth = RecruitmentApplication::where('status', 'hired')->whereMonth('updated_at', now()->subMonth()->month)->count();
        $hiredGrowth = $hiredLastMonth > 0 ? round((($hiredThisMonth - $hiredLastMonth) / $hiredLastMonth) * 100) : ($hiredThisMonth > 0 ? 100 : 0);

        // Job performance table
        $jobs = RecruitmentJob::with(['workUnit', 'applications'])
            ->withCount('applications')
            ->orderBy('applications_count', 'desc')->limit(10)->get();

        $jobPerformance = $jobs->map(fn($job) => [
            'kode'          => $job->kode_lowongan,
            'judul'         => $job->judul,
            'unit'          => $job->workUnit?->name ?? '-',
            'kuota'         => $job->kuota,
            'terisi'        => $job->kuota_terisi ?? 0,
            'total_pelamar' => $job->applications_count,
            'konversi'      => $job->kuota > 0 ? round(($job->applications_count / $job->kuota) * 100) : 0,
            'sisa_kuota'    => max(0, $job->kuota - ($job->kuota_terisi ?? 0)),
        ])->toArray();

        // Applicant demographics (gender)
        $genderStats = DB::table('recruitment_applications')
            ->join('recruitment_profiles', 'recruitment_applications.recruitment_profile_id', '=', 'recruitment_profiles.id')
            ->selectRaw("SUM(CASE WHEN recruitment_profiles.jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki_laki,
                         SUM(CASE WHEN recruitment_profiles.jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan")
            ->first();

        // Applicant demographics (age groups)
        $now = now();
        $ageStats = DB::table('recruitment_applications')
            ->join('recruitment_profiles', 'recruitment_applications.recruitment_profile_id', '=', 'recruitment_profiles.id')
            ->whereNotNull('recruitment_profiles.tanggal_lahir')
            ->selectRaw("
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) < 25 THEN 1 ELSE 0 END) as kurang_25,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) BETWEEN 25 AND 30 THEN 1 ELSE 0 END) as 25_30,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) BETWEEN 31 AND 35 THEN 1 ELSE 0 END) as 31_35,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) BETWEEN 36 AND 40 THEN 1 ELSE 0 END) as 36_40,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, recruitment_profiles.tanggal_lahir, CURDATE()) > 40 THEN 1 ELSE 0 END) as lebih_40
            ")->first();

        // Trends — monthly applications and hires (last 12 months)
        $applicationsTrend = RecruitmentApplication::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key,
                         COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->get()
            ->map(fn($r) => [
                'month_key' => $r->month_key,
                'period'    => \Carbon\Carbon::createFromFormat('Y-m', $r->month_key)->format('M Y'),
                'total'     => $r->total,
            ]);

        $hiresTrend = RecruitmentApplication::query()
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as month_key,
                         COUNT(*) as total")
            ->where('status', 'diterima')
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(updated_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(updated_at, '%Y-%m')")
            ->get()
            ->map(fn($r) => [
                'month_key' => $r->month_key,
                'period'    => \Carbon\Carbon::createFromFormat('Y-m', $r->month_key)->format('M Y'),
                'total'     => $r->total,
            ]);

        return [
            'total_jobs'          => $totalJobs,
            'active_jobs'         => $activeJobs,
            'total_applications'  => $totalApplications,
            'application_growth'  => $appGrowth,
            'hired_count'         => $hiredCount,
            'hired_growth'        => $hiredGrowth,
            'application_rate'    => $totalJobs > 0 ? round(($totalApplications / max(1, $activeJobs)) * 10) / 10 : 0,
            'time_to_hire'        => $timeToHire,
            'hiring_funnel'       => $hiringFunnel,
            'job_performance'     => $jobPerformance,
            'applicant_demographics' => [
                'gender' => [
                    'L' => (int) ($genderStats->laki_laki ?? 0),
                    'P' => (int) ($genderStats->perempuan ?? 0),
                ],
                'age_groups' => [
                    '< 25'  => (int) ($ageStats->kurang_25 ?? 0),
                    '25-30' => (int) ($ageStats->{'25_30'} ?? 0),
                    '31-35' => (int) ($ageStats->{'31_35'} ?? 0),
                    '36-40' => (int) ($ageStats->{'36_40'} ?? 0),
                    '> 40'  => (int) ($ageStats->lebih_40 ?? 0),
                ],
            ],
            'trends' => [
                'applications' => $applicationsTrend,
                'hires'       => $hiresTrend,
            ],
        ];
    }
}
