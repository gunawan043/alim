<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentPipeline;
use App\Models\RecruitmentJob;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentPipelineStage;
use App\Models\RecruitmentApplicationStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecruitmentPipelineController extends Controller
{
    /**
     * Display pipeline for a specific job.
     */
    public function index($jobId)
    {
        $job = RecruitmentJob::with(['pipeline.stages', 'applications'])->findOrFail($jobId);
        $pipeline = $job->pipeline;
        
        // Get applications grouped by stage
        $applicationsByStage = [];
        foreach ($pipeline->stages as $stage) {
            $applicationsByStage[$stage->id] = [
                'stage' => $stage,
                'applications' => RecruitmentApplication::where('recruitment_job_id', $jobId)
                    ->where('current_stage_id', $stage->id)
                    ->with('recruitmentProfile.user')
                    ->get()
            ];
        }
        
        return view('recruitment.pipeline.index', compact('job', 'pipeline', 'applicationsByStage'));
    }

    /**
     * Show pipeline board view.
     */
    public function board($jobId)
    {
        $userId = request()->route('userId');
        $job = RecruitmentJob::with(['pipeline.stages'])->findOrFail($jobId);
        
        // Get all applications with their stages
        $applications = RecruitmentApplication::where('recruitment_job_id', $jobId)
            ->with(['recruitmentProfile.user', 'currentStage'])
            ->get();
        
        // Organize by stage for kanban board
        $boardData = [];
        foreach ($job->pipeline->stages->sortBy('urutan') as $stage) {
            $boardData[$stage->id] = [
                'stage' => $stage,
                'applications' => $applications->where('current_stage_id', $stage->id)
            ];
        }
        
        return view('recruitment.pipeline.board', compact('job', 'boardData', 'userId'));
    }

    /**
     * Move application to next stage.
     */
    public function moveToNextStage(Request $request, $applicationId)
    {
        $application = RecruitmentApplication::findOrFail($applicationId);
        $currentStage = $application->currentStage;
        
        if (!$currentStage) {
            return response()->json(['error' => 'No current stage found'], 400);
        }
        
        // Get next stage
        $nextStage = RecruitmentPipelineStage::where('recruitment_pipeline_id', $currentStage->recruitment_pipeline_id)
            ->where('urutan', '>', $currentStage->urutan)
            ->orderBy('urutan')
            ->first();
        
        if (!$nextStage) {
            // This is the final stage
            $application->update([
                'status' => 'selesai',
                'selesai_at' => now()
            ]);
        } else {
            // Move to next stage
            $application->update([
                'current_stage_id' => $nextStage->id,
                'status' => 'dalam_proses'
            ]);
            
            // Create stage record
            $application->stages()->create([
                'recruitment_pipeline_stage_id' => $nextStage->id,
                'status' => 'menunggu',
                'urutan' => $nextStage->urutan
            ]);
        }
        
        // Log activity
        $this->logPipelineActivity($application, $currentStage, $nextStage);
        
        return response()->json(['success' => true, 'next_stage' => $nextStage]);
    }

    /**
     * Move application to a specific stage (drag-and-drop).
     */
    public function moveToStage(Request $request, $applicationId)
    {
        $request->validate([
            'stage_id' => 'required|exists:recruitment_pipeline_stages,id'
        ]);

        $application = RecruitmentApplication::findOrFail($applicationId);
        $currentStage = $application->currentStage;
        $targetStage = RecruitmentPipelineStage::find($request->stage_id);

        $application->update([
            'current_stage_id' => $request->stage_id,
            'status' => 'dalam_proses'
        ]);

        $application->stages()->create([
            'recruitment_pipeline_stage_id' => $request->stage_id,
            'status' => 'menunggu',
            'urutan' => $targetStage->urutan
        ]);

        $this->logPipelineActivity($application, $currentStage, $targetStage);

        return response()->json(['success' => true]);
    }

    /**
     * Create custom pipeline for job.
     */
    public function createPipeline(Request $request, $jobId)
    {
        $request->validate([
            'nama_tahapan' => 'required|array',
            'nama_tahapan.*' => 'required|string',
            'durasi' => 'array',
            'durasi.*' => 'nullable|integer|min:1'
        ]);

        $job = RecruitmentJob::findOrFail($jobId);
        
        // Delete existing pipeline if any
        if ($job->pipeline) {
            $job->pipeline->stages()->delete();
            $job->pipeline->delete();
        }
        
        // Create new pipeline
        $pipeline = RecruitmentPipeline::create([
            'recruitment_job_id' => $jobId,
            'nama_tahapan' => 'Pipeline ' . $job->judul,
            'is_active' => true,
            'created_by' => Auth::id()
        ]);
        
        // Create stages
        foreach ($request->nama_tahapan as $index => $nama) {
            RecruitmentPipelineStage::create([
                'recruitment_pipeline_id' => $pipeline->id,
                'nama_tahapan' => $nama,
                'urutan' => $index + 1,
                'durasi_hari' => $request->durasi[$index] ?? 1,
                'is_wajib' => true,
                'warna' => $this->getStageColor($index)
            ]);
        }
        
        $userId = request()->route('userId');

        return redirect()->route('user.ats.pipeline.index', ['userId' => $userId, 'jobId' => $jobId])
            ->with('success', 'Pipeline berhasil dibuat');
    }

    /**
     * Get stage statistics.
     */
    public function getStatistics($jobId)
    {
        $job = RecruitmentJob::with('pipeline.stages')->findOrFail($jobId);
        
        $stats = [
            'total_applications' => RecruitmentApplication::where('recruitment_job_id', $jobId)->count(),
            'applications_by_stage' => [],
            'average_time_per_stage' => [],
            'conversion_rate' => []
        ];
        
        foreach ($job->pipeline->stages as $stage) {
            $count = RecruitmentApplication::where('recruitment_job_id', $jobId)
                ->where('current_stage_id', $stage->id)
                ->count();
            
            $stats['applications_by_stage'][$stage->nama_tahapan] = $count;
            
            // Calculate average time in this stage
            $avgTime = $this->calculateAverageStageTime($stage->id);
            $stats['average_time_per_stage'][$stage->nama_tahapan] = $avgTime;
            
            // Calculate conversion rate to next stage
            $conversion = $this->calculateConversionRate($stage->id);
            $stats['conversion_rate'][$stage->nama_tahapan] = $conversion;
        }
        
        return response()->json($stats);
    }

    /**
     * Helper: Calculate average time in stage.
     */
    private function calculateAverageStageTime($stageId)
    {
        $stages = RecruitmentApplicationStage::where('recruitment_pipeline_stage_id', $stageId)
            ->whereNotNull('selesai_at')
            ->get();
        
        if ($stages->isEmpty()) {
            return 0;
        }
        
        $totalDays = $stages->sum(function ($stage) {
            return $stage->created_at->diffInDays($stage->selesai_at);
        });
        
        return round($totalDays / $stages->count());
    }

    /**
     * Helper: Calculate conversion rate.
     */
    private function calculateConversionRate($stageId)
    {
        $stage = RecruitmentPipelineStage::find($stageId);
        if (!$stage) {
            return 0;
        }
        
        $totalInStage = RecruitmentApplication::where('current_stage_id', $stageId)->count();
        
        $nextStage = RecruitmentPipelineStage::where('recruitment_pipeline_id', $stage->recruitment_pipeline_id)
            ->where('urutan', '>', $stage->urutan)
            ->first();
        
        if (!$nextStage) {
            // Final stage - calculate acceptance rate
            $accepted = RecruitmentApplication::where('recruitment_job_id', $stage->recruitmentPipeline->recruitment_job_id)
                ->where('status', 'diterima')
                ->count();
            
            return $totalInStage > 0 ? round(($accepted / $totalInStage) * 100) : 0;
        }
        
        $movedToNext = RecruitmentApplication::where('current_stage_id', $nextStage->id)->count();
        
        return $totalInStage > 0 ? round(($movedToNext / $totalInStage) * 100) : 0;
    }

    /**
     * Helper: Get stage color.
     */
    private function getStageColor($index)
    {
        $colors = [
            '#4299E1', // blue
            '#48BB78', // green
            '#ECC94B', // yellow
            '#9F7AEA', // purple
            '#ED8936', // orange
            '#F56565', // red
            '#667EEA', // indigo
            '#38B2AC'  // teal
        ];
        
        return $colors[$index % count($colors)];
    }

    /**
     * Helper: Log pipeline activity.
     */
    private function logPipelineActivity($application, $fromStage, $toStage = null)
    {
        activity()
            ->performedOn($application)
            ->causedBy(Auth::user())
            ->withProperties([
                'from_stage' => $fromStage ? $fromStage->nama_tahapan : null,
                'to_stage' => $toStage ? $toStage->nama_tahapan : 'Selesai',
                'application_id' => $application->id,
                'job_id' => $application->recruitment_job_id
            ])
            ->log('Application moved in pipeline');
    }
}