<?php

namespace App\Http\Controllers;

use App\Services\RecruitmentReportService;
use Illuminate\Http\Request;

class RecruitmentReportController extends Controller
{
    protected $reportService;

    public function __construct(RecruitmentReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get dashboard report
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'month');
        
        $data = $this->reportService->getDashboardStats($period);

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => $period,
            'generated_at' => now()
        ]);
    }

    /**
     * Get job performance report
     */
    public function jobPerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'job_type' => 'nullable|string'
        ]);

        $dateRange = [
            'start' => $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonth(),
            'end' => $request->end_date ? Carbon::parse($request->end_date) : Carbon::now(),
            'days' => 30
        ];

        $data = $this->reportService->getJobPerformance($dateRange);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Export report
     */
    public function export(Request $request)
    {
        $request->validate([
            'type' => 'required|in:overview,funnel,time-to-hire,jobs,demographics',
            'format' => 'required|in:pdf,excel,csv',
            'period' => 'required|in:week,month,quarter,year'
        ]);

        try {
            $file = $this->reportService->exportReport(
                $request->type,
                $request->format,
                $request->period
            );

            return $file;
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal export report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scheduled reports
     */
    public function scheduledReports()
    {
        $reports = \App\Models\ScheduledReport::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Schedule a report
     */
    public function scheduleReport(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'frequency' => 'required|in:daily,weekly,monthly',
            'recipients' => 'required|array',
            'recipients.*' => 'email'
        ]);

        $schedule = \App\Models\ScheduledReport::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'type' => $request->type,
            'format' => $request->format,
            'frequency' => $request->frequency,
            'recipients' => $request->recipients,
            'last_sent_at' => null,
            'next_send_at' => $this->calculateNextSend($request->frequency),
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    /**
     * Calculate next send date
     */
    protected function calculateNextSend($frequency)
    {
        switch ($frequency) {
            case 'daily':
                return Carbon::tomorrow()->setTime(8, 0);
            case 'weekly':
                return Carbon::next(Carbon::MONDAY)->setTime(8, 0);
            case 'monthly':
                return Carbon::now()->addMonth()->firstOfMonth()->setTime(8, 0);
            default:
                return Carbon::now()->addDay();
        }
    }
}