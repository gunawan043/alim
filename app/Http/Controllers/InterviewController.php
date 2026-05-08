<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentApplicationStage;
use App\Models\RecruitmentApplication;
use App\Models\User;
use App\Services\NotificationUniversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterviewController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of interviews.
     */
    public function index(Request $request)
    {
        $query = RecruitmentApplicationStage::with([
            'recruitmentApplication.recruitmentProfile.user',
            'recruitmentApplication.recruitmentJob',
            'recruitmentPipelineStage',
            'penilai'
        ])->whereHas('recruitmentPipelineStage', function ($q) {
            $q->whereIn('nama_tahapan', ['Wawancara HR', 'Wawancara User', 'Tes Tertulis', 'Tes Praktek']);
        });

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('jadwal_mulai', $request->date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by interviewer
        if ($request->has('penilai_id')) {
            $query->where('penilai_id', $request->penilai_id);
        }

        $interviews = $query->orderBy('jadwal_mulai')->get();

        // Group by date for calendar
        $calendarEvents = $interviews->map(function($interview) {
            return [
                'id' => $interview->id,
                'title' => $interview->recruitmentApplication->recruitmentProfile->user->name . ' - ' . $interview->recruitmentPipelineStage->nama_tahapan,
                'start' => $interview->jadwal_mulai->format('Y-m-d H:i:s'),
                'end' => $interview->jadwal_selesai ? $interview->jadwal_selesai->format('Y-m-d H:i:s') : null,
                'className' => 'bg-' . ($interview->status == 'selesai' ? 'success' : ($interview->status == 'sedang_berlangsung' ? 'warning' : 'info')) . '-subtle',
                'url' => route('recruitment.interviews.show', $interview->id)
            ];
        });

        $todayInterviews = $interviews->filter(function($i) {
            return $i->jadwal_mulai->isToday();
        });

        $upcomingInterviews = $interviews->filter(function($i) {
            return $i->jadwal_mulai->isFuture();
        });

        return view('recruitment.interviews.index', compact('interviews', 'calendarEvents', 'todayInterviews', 'upcomingInterviews'));
    }

    /**
     * Get calendar events.
     */
    public function calendarEvents(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $stages = RecruitmentApplicationStage::with([
            'recruitmentApplication.recruitmentProfile.user',
            'recruitmentApplication.recruitmentJob',
            'recruitmentPipelineStage'
        ])->whereBetween('jadwal_mulai', [$start, $end])
          ->get();

        $events = $stages->map(function($stage) {
            $color = 'primary';
            if ($stage->status == 'selesai') $color = 'success';
            elseif ($stage->status == 'tidak_lolos') $color = 'danger';
            elseif ($stage->status == 'sedang_berlangsung') $color = 'warning';

            return [
                'id' => $stage->id,
                'title' => $stage->recruitmentPipelineStage->nama_tahapan . ' - ' . $stage->recruitmentApplication->recruitmentProfile->user->name,
                'start' => $stage->jadwal_mulai->format('Y-m-d H:i:s'),
                'end' => $stage->jadwal_selesai ? $stage->jadwal_selesai->format('Y-m-d H:i:s') : null,
                'backgroundColor' => 'var(--vz-' . $color . ')',
                'borderColor' => 'var(--vz-' . $color . ')',
                'extendedProps' => [
                    'candidate' => $stage->recruitmentApplication->recruitmentProfile->user->name,
                    'position' => $stage->recruitmentApplication->recruitmentJob->judul,
                    'status' => $stage->status,
                    'location' => $stage->lokasi
                ]
            ];
        });

        return response()->json($events);
    }

    /**
     * Store a newly created interview.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:recruitment_applications,id',
            'stage_name' => 'required|string|exists:recruitment_pipeline_stages,nama_tahapan',
            'jadwal_mulai' => 'required|date',
            'jadwal_selesai' => 'nullable|date|after:jadwal_mulai',
            'lokasi' => 'nullable|string',
            'penilai_id' => 'nullable|exists:users,id',
            'catatan' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $application = RecruitmentApplication::find($validated['application_id']);

            // Look up the pipeline stage by name
            $pipelineStage = \App\Models\RecruitmentPipelineStage::where('nama_tahapan', $validated['stage_name'])->firstOrFail();

            // Get next order
            $lastOrder = $application->stages()->max('urutan') ?? 0;

            $stage = $application->stages()->create([
                'recruitment_pipeline_stage_id' => $pipelineStage->id,
                'urutan' => $lastOrder + 1,
                'status' => 'menunggu',
                'jadwal_mulai' => $validated['jadwal_mulai'],
                'jadwal_selesai' => $validated['jadwal_selesai'] ?? null,
                'lokasi' => $validated['lokasi'] ?? null,
                'penilai_id' => $validated['penilai_id'] ?? null,
                'catatan' => $validated['catatan'] ?? null
            ]);

            // Update application status
            $application->status = str_contains($validated['stage_name'], 'Wawancara') ? 'wawancara' : 'tes_tertulis';
            $application->save();

            // Notify candidate
            $this->notificationService->send($application->recruitmentProfile->user_id, [
                'module' => 'recruitment',
                'type' => 'info',
                'action' => 'interview_scheduled',
                'title' => 'Jadwal ' . $validated['stage_name'],
                'message' => "Anda dijadwalkan mengikuti {$validated['stage_name']} pada " . date('d M Y H:i', strtotime($validated['jadwal_mulai'])),
                'data' => [
                    'stage' => $validated['stage_name'],
                    'datetime' => $validated['jadwal_mulai'],
                    'location' => $validated['lokasi']
                ],
                'action_url' => route('recruitment.applications.show', $application->id),
                'priority' => 'high',
                'send_email' => true,
                'send_whatsapp' => true
            ]);

            // Notify interviewer
            if ($validated['penilai_id']) {
                $this->notificationService->send($validated['penilai_id'], [
                    'module' => 'recruitment',
                    'type' => 'info',
                    'action' => 'interview_assigned',
                    'title' => 'Jadwal Interview',
                    'message' => "Anda ditugaskan sebagai penilai untuk {$application->recruitmentProfile->user->name} pada " . date('d M Y H:i', strtotime($validated['jadwal_mulai'])),
                    'action_url' => route('recruitment.interviews.show', $stage->id),
                    'priority' => 'high'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal interview berhasil disimpan',
                'data' => $stage
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan jadwal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display interview details.
     */
    public function show(RecruitmentApplicationStage $interview)
    {
        $interview->load([
            'recruitmentApplication.recruitmentProfile.user',
            'recruitmentApplication.recruitmentJob',
            'penilai'
        ]);

        return view('recruitment.interviews.show', compact('interview'));
    }

    /**
     * Reschedule interview.
     */
    public function reschedule(Request $request, RecruitmentApplicationStage $interview)
    {
        $validated = $request->validate([
            'jadwal_mulai' => 'required|date',
            'jadwal_selesai' => 'nullable|date|after:jadwal_mulai',
            'alasan' => 'required|string'
        ]);

        $oldDate = $interview->jadwal_mulai->format('d M Y H:i');

        $interview->jadwal_mulai = $validated['jadwal_mulai'];
        $interview->jadwal_selesai = $validated['jadwal_selesai'] ?? null;
        $interview->save();

        // Notify candidate
        $this->notificationService->send($interview->recruitmentApplication->recruitmentProfile->user_id, [
            'module' => 'recruitment',
            'type' => 'warning',
            'action' => 'interview_rescheduled',
            'title' => 'Jadwal Interview Diubah',
            'message' => "Jadwal {$interview->recruitmentPipelineStage->nama_tahapan} Anda telah diubah dari {$oldDate} menjadi " . date('d M Y H:i', strtotime($validated['jadwal_mulai'])),
            'action_url' => route('recruitment.applications.show', $interview->recruitmentApplication->id),
            'priority' => 'high',
            'send_email' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil diubah'
        ]);
    }

    /**
     * Mark interview as complete and add feedback.
     */
    public function markComplete(Request $request, RecruitmentApplicationStage $interview)
    {
        $validated = $request->validate([
            'hasil' => 'required|in:lolos,tidak_lolos',
            'nilai' => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
            'rekomendasi' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $interview->status = $validated['hasil'] == 'lolos' ? 'lolos' : 'tidak_lolos';
            $interview->nilai = $validated['nilai'] ?? null;
            $interview->catatan = $validated['feedback'];
            $interview->selesai_at = now();
            $interview->save();

            $application = $interview->recruitmentApplication;

            // Update application status based on result
            $stageName = $interview->recruitmentPipelineStage->nama_tahapan;
            if ($validated['hasil'] == 'lolos') {
                if (in_array($stageName, ['Wawancara HR', 'Wawancara User'])) {
                    $application->status = 'lolos_wawancara';
                } else {
                    $application->status = 'lolos_tes';
                }
            } else {
                if (in_array($stageName, ['Wawancara HR', 'Wawancara User'])) {
                    $application->status = 'tidak_lolos_wawancara';
                } else {
                    $application->status = 'tidak_lolos_tes';
                }
            }

            $application->nilai_akhir = $validated['nilai'] ?? $application->nilai_akhir;
            $application->save();

            // Notify candidate
            $statusText = $validated['hasil'] == 'lolos' ? 'Lolos' : 'Tidak Lolos';
            $this->notificationService->send($application->recruitmentProfile->user_id, [
                'module' => 'recruitment',
                'type' => $validated['hasil'] == 'lolos' ? 'success' : 'error',
                'action' => 'interview_result',
                'title' => 'Hasil ' . $stageName,
                'message' => "Hasil {$stageName} Anda: {$statusText}" . ($validated['feedback'] ? "\nCatatan: {$validated['feedback']}" : ''),
                'action_url' => route('recruitment.applications.show', $application->id),
                'priority' => 'high',
                'send_email' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hasil interview berhasil disimpan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan hasil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add feedback to interview.
     */
    public function addFeedback(Request $request, RecruitmentApplicationStage $interview)
    {
        $validated = $request->validate([
            'feedback' => 'required|string',
            'rekomendasi' => 'nullable|string'
        ]);

        $interview->catatan = $validated['feedback'] . ($validated['rekomendasi'] ? "\nRekomendasi: " . $validated['rekomendasi'] : '');
        $interview->save();

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil ditambahkan'
        ]);
    }
}