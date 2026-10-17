<?php

namespace App\Services;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJob;
use App\Models\RecruitmentProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecruitmentReportService
{
    protected $cacheTime = 3600; // 1 jam

    /**
     * Get dashboard overview statistics
     */
    public function getDashboardStats($period = 'month')
    {
        $cacheKey = "recruitment_dashboard_stats_{$period}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            return [
                'overview' => $this->getOverviewStats($dateRange),
                'hiring_funnel' => $this->getHiringFunnel($dateRange),
                'time_to_hire' => $this->getTimeToHire($dateRange),
                'source_effectiveness' => $this->getSourceEffectiveness($dateRange),
                'job_performance' => $this->getJobPerformance($dateRange),
                'applicant_demographics' => $this->getApplicantDemographics($dateRange),
                'trends' => $this->getTrends($period),
                'top_performers' => $this->getTopPerformers($dateRange),
            ];
        });
    }

    /**
     * Get overview statistics
     */
    protected function getOverviewStats($dateRange)
    {
        $current = [
            'total_jobs' => RecruitmentJob::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'active_jobs' => RecruitmentJob::where('status', 'aktif')->count(),
            'total_applications' => RecruitmentApplication::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'new_profiles' => RecruitmentProfile::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'hired_count' => RecruitmentApplication::where('status', 'diterima')
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'rejected_count' => RecruitmentApplication::where('status', 'ditolak')
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
        ];

        // Calculate conversion rates
        $current['application_rate'] = $current['total_applications'] > 0
            ? round(($current['hired_count'] / $current['total_applications']) * 100, 2)
            : 0;

        $current['job_fill_rate'] = $current['total_jobs'] > 0
            ? round(($current['hired_count'] / $current['total_jobs']) * 100, 2)
            : 0;

        // Get previous period for comparison
        $previousRange = [
            'start' => Carbon::parse($dateRange['start'])->subDays($dateRange['days']),
            'end' => Carbon::parse($dateRange['start'])->subDay(),
        ];

        $previous = [
            'total_applications' => RecruitmentApplication::whereBetween('created_at', [$previousRange['start'], $previousRange['end']])->count(),
            'hired_count' => RecruitmentApplication::where('status', 'diterima')
                ->whereBetween('updated_at', [$previousRange['start'], $previousRange['end']])
                ->count(),
        ];

        // Calculate growth
        $current['application_growth'] = $previous['total_applications'] > 0
            ? round((($current['total_applications'] - $previous['total_applications']) / $previous['total_applications']) * 100, 2)
            : 100;

        $current['hired_growth'] = $previous['hired_count'] > 0
            ? round((($current['hired_count'] - $previous['hired_count']) / $previous['hired_count']) * 100, 2)
            : 100;

        return $current;
    }

    /**
     * Get hiring funnel data
     */
    protected function getHiringFunnel($dateRange)
    {
        $stages = [
            'applications' => RecruitmentApplication::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'administrasi_lolos' => RecruitmentApplication::whereIn('status', ['lolos_administrasi', 'tes_tertulis', 'wawancara', 'diterima'])
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'tes_lolos' => RecruitmentApplication::whereIn('status', ['lolos_tes_tertulis', 'wawancara', 'diterima'])
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'wawancara_lolos' => RecruitmentApplication::whereIn('status', ['lolos_wawancara', 'diterima'])
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'offered' => RecruitmentApplication::where('status', 'penawaran_kerja')
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'hired' => RecruitmentApplication::where('status', 'diterima')
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
        ];

        // Calculate conversion rates
        $funnel = [];
        $prev = $stages['applications'];

        foreach ($stages as $stage => $count) {
            $funnel[$stage] = [
                'count' => $count,
                'conversion_rate' => $prev > 0 ? round(($count / $prev) * 100, 2) : 0,
                'dropoff' => $prev - $count,
                'dropoff_rate' => $prev > 0 ? round((($prev - $count) / $prev) * 100, 2) : 0,
            ];
            $prev = $count;
        }

        return $funnel;
    }

    /**
     * Get time to hire analytics
     */
    protected function getTimeToHire($dateRange)
    {
        $applications = RecruitmentApplication::where('status', 'diterima')
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->with('recruitmentJob')
            ->get();

        if ($applications->isEmpty()) {
            return [
                'average_days' => 0,
                'median_days' => 0,
                'min_days' => 0,
                'max_days' => 0,
                'by_job_type' => [],
            ];
        }

        $daysToHire = [];
        $byJobType = [];

        foreach ($applications as $app) {
            $created = Carbon::parse($app->created_at);
            $hired = Carbon::parse($app->updated_at);
            $days = $created->diffInDays($hired);

            $daysToHire[] = $days;

            $jobType = $app->recruitmentJob->jenis_pegawai ?? 'unknown';
            if (! isset($byJobType[$jobType])) {
                $byJobType[$jobType] = [];
            }
            $byJobType[$jobType][] = $days;
        }

        // Calculate statistics
        $stats = [
            'average_days' => round(array_sum($daysToHire) / count($daysToHire), 1),
            'median_days' => $this->calculateMedian($daysToHire),
            'min_days' => min($daysToHire),
            'max_days' => max($daysToHire),
            'distribution' => $this->getDistribution($daysToHire, [7, 14, 30, 60, 90]),
            'by_job_type' => [],
        ];

        foreach ($byJobType as $type => $days) {
            $stats['by_job_type'][$type] = [
                'average' => round(array_sum($days) / count($days), 1),
                'count' => count($days),
            ];
        }

        return $stats;
    }

    /**
     * Get source effectiveness
     */
    protected function getSourceEffectiveness($dateRange)
    {
        // Asumsi ada field 'source' di recruitment_applications
        // Bisa dari referensi, website, sosial media, dll

        $sources = RecruitmentApplication::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select('source', DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'diterima' then 1 else 0 end) as hired"))
            ->groupBy('source')
            ->get();

        $totalApplications = $sources->sum('total');
        $totalHired = $sources->sum('hired');

        $result = [];
        foreach ($sources as $source) {
            $result[] = [
                'source' => $source->source ?? 'direct',
                'applications' => $source->total,
                'percentage' => $totalApplications > 0 ? round(($source->total / $totalApplications) * 100, 2) : 0,
                'hired' => $source->hired,
                'success_rate' => $source->total > 0 ? round(($source->hired / $source->total) * 100, 2) : 0,
                'contribution_to_hired' => $totalHired > 0 ? round(($source->hired / $totalHired) * 100, 2) : 0,
            ];
        }

        return $result;
    }

    /**
     * Get job performance metrics
     */
    protected function getJobPerformance($dateRange)
    {
        return RecruitmentJob::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->withCount(['applications', 'applications as hired_count' => function ($q) {
                $q->where('status', 'diterima');
            }])
            ->with(['workUnit', 'creator'])
            ->orderBy('applications_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'kode' => $job->kode_lowongan,
                    'judul' => $job->judul,
                    'unit' => $job->workUnit->name ?? '-',
                    'jenis' => $job->jenis_pegawai,
                    'kuota' => $job->kuota,
                    'terisi' => $job->hired_count,
                    'sisa_kuota' => $job->kuota - $job->hired_count,
                    'total_pelamar' => $job->applications_count,
                    'konversi' => $job->applications_count > 0
                        ? round(($job->hired_count / $job->applications_count) * 100, 2)
                        : 0,
                    'created_by' => $job->creator->name ?? '-',
                    'created_at' => $job->created_at->format('Y-m-d'),
                ];
            });
    }

    /**
     * Get applicant demographics
     */
    protected function getApplicantDemographics($dateRange)
    {
        $profiles = RecruitmentProfile::whereHas('applications', function ($q) use ($dateRange) {
            $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })->get();

        return [
            'gender' => [
                'L' => $profiles->where('jenis_kelamin', 'L')->count(),
                'P' => $profiles->where('jenis_kelamin', 'P')->count(),
            ],
            'age_groups' => [
                '< 25' => $profiles->filter(fn ($p) => $p->tanggal_lahir && $p->tanggal_lahir->age < 25)->count(),
                '25-30' => $profiles->filter(fn ($p) => $p->tanggal_lahir && $p->tanggal_lahir->age >= 25 && $p->tanggal_lahir->age <= 30)->count(),
                '31-35' => $profiles->filter(fn ($p) => $p->tanggal_lahir && $p->tanggal_lahir->age >= 31 && $p->tanggal_lahir->age <= 35)->count(),
                '36-40' => $profiles->filter(fn ($p) => $p->tanggal_lahir && $p->tanggal_lahir->age >= 36 && $p->tanggal_lahir->age <= 40)->count(),
                '> 40' => $profiles->filter(fn ($p) => $p->tanggal_lahir && $p->tanggal_lahir->age > 40)->count(),
            ],
            'education' => $this->getEducationDemographics($dateRange),
            'location' => $profiles->groupBy('provinsi')
                ->map(fn ($group, $prov) => [
                    'provinsi' => $prov ?: 'Tidak diketahui',
                    'total' => $group->count(),
                ])
                ->values()
                ->take(10),
            'marital_status' => $profiles->groupBy('status_perkawinan')
                ->map(fn ($group, $status) => [
                    'status' => $status,
                    'total' => $group->count(),
                ])
                ->values(),
            'religion' => $profiles->groupBy('agama')
                ->map(fn ($group, $agama) => [
                    'agama' => $agama ?: 'Tidak diketahui',
                    'total' => $group->count(),
                ])
                ->values(),
        ];
    }

    /**
     * Get education demographics
     */
    protected function getEducationDemographics($dateRange)
    {
        $educations = DB::table('recruitment_educations')
            ->join('recruitment_profiles', 'recruitment_educations.recruitment_profile_id', '=', 'recruitment_profiles.id')
            ->join('recruitment_applications', 'recruitment_profiles.id', '=', 'recruitment_applications.recruitment_profile_id')
            ->whereBetween('recruitment_applications.created_at', [$dateRange['start'], $dateRange['end']])
            ->select('recruitment_educations.jenjang', DB::raw('count(*) as total'))
            ->groupBy('recruitment_educations.jenjang')
            ->get();

        $total = $educations->sum('total');

        return $educations->map(function ($edu) use ($total) {
            return [
                'jenjang' => $edu->jenjang,
                'total' => $edu->total,
                'percentage' => $total > 0 ? round(($edu->total / $total) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Get trends over time
     */
    protected function getTrends($period)
    {
        $interval = $period == 'week' ? 'day' : ($period == 'month' ? 'week' : 'month');
        $format = $interval == 'day' ? '%Y-%m-%d' : ($interval == 'week' ? '%Y-%u' : '%Y-%m');

        $applications = RecruitmentApplication::select(
            DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
            DB::raw('count(*) as total')
        )
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit($period == 'week' ? 7 : ($period == 'month' ? 12 : 12))
            ->get();

        $hires = RecruitmentApplication::where('status', 'diterima')
            ->select(
                DB::raw("DATE_FORMAT(updated_at, '{$format}') as period"),
                DB::raw('count(*) as total')
            )
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit($period == 'week' ? 7 : ($period == 'month' ? 12 : 12))
            ->get();

        return [
            'applications' => $applications,
            'hires' => $hires,
        ];
    }

    /**
     * Get top performers (best applicants)
     */
    protected function getTopPerformers($dateRange)
    {
        return RecruitmentApplication::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('nilai_akhir')
            ->with(['recruitmentProfile.user', 'recruitmentJob'])
            ->orderBy('nilai_akhir', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($app) {
                return [
                    'nama' => $app->recruitmentProfile->user->name,
                    'posisi' => $app->recruitmentJob->judul,
                    'nilai' => $app->nilai_akhir,
                    'status' => $app->status,
                    'tanggal_melamar' => $app->tanggal_melamar,
                ];
            });
    }

    /**
     * Export report to various formats
     */
    public function exportReport($type, $format = 'pdf', $period = 'month')
    {
        $data = $this->getDashboardStats($period);

        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($type, $data, $period);
            case 'excel':
                return $this->exportToExcel($type, $data, $period);
            case 'csv':
                return $this->exportToCsv($type, $data, $period);
            default:
                throw new \Exception("Format {$format} tidak didukung");
        }
    }

    /**
     * Export to PDF
     */
    protected function exportToPdf($type, $data, $period)
    {
        $pdf = \PDF::loadView('reports.recruitment.'.$type, [
            'data' => $data,
            'period' => $period,
            'generated_at' => now(),
        ]);

        return $pdf->download("recruitment-report-{$type}-{$period}-".now()->format('Y-m-d').'.pdf');
    }

    /**
     * Export to Excel
     */
    protected function exportToExcel($type, $data, $period)
    {
        // Implementasi Excel export
        // Bisa menggunakan Maatwebsite\Excel
    }

    /**
     * Export to CSV
     */
    protected function exportToCsv($type, $data, $period)
    {
        // Implementasi CSV export
    }

    /**
     * Helper: Get date range based on period
     */
    protected function getDateRange($period)
    {
        $end = Carbon::now();

        switch ($period) {
            case 'week':
                $start = Carbon::now()->subWeek();
                $days = 7;
                break;
            case 'month':
                $start = Carbon::now()->subMonth();
                $days = 30;
                break;
            case 'quarter':
                $start = Carbon::now()->subMonths(3);
                $days = 90;
                break;
            case 'year':
                $start = Carbon::now()->subYear();
                $days = 365;
                break;
            default:
                $start = Carbon::now()->subMonth();
                $days = 30;
        }

        return [
            'start' => $start,
            'end' => $end,
            'days' => $days,
        ];
    }

    /**
     * Helper: Calculate median
     */
    protected function calculateMedian($arr)
    {
        sort($arr);
        $count = count($arr);
        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return $arr[$middle];
        } else {
            return ($arr[$middle] + $arr[$middle + 1]) / 2;
        }
    }

    /**
     * Helper: Get distribution
     */
    protected function getDistribution($arr, $thresholds)
    {
        $distribution = [];
        $prev = 0;

        foreach ($thresholds as $threshold) {
            $count = count(array_filter($arr, fn ($v) => $v > $prev && $v <= $threshold));
            $distribution["{$prev}-{$threshold}"] = $count;
            $prev = $threshold;
        }

        $count = count(array_filter($arr, fn ($v) => $v > $prev));
        $distribution["> {$prev}"] = $count;

        return $distribution;
    }
}
