<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJob;
use App\Models\User;
use App\Models\RecruitmentProfile;
use App\Services\NotificationUniversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of applications.
     */
    public function index(Request $request)
    {
        $query = RecruitmentApplication::with([
            'recruitmentProfile.user',
            'recruitmentJob',
            'stages'
        ]);

        // Filter by job
        if ($request->has('job_id')) {
            $query->where('recruitment_job_id', $request->job_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0], $dates[1]]);
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('recruitmentProfile.user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('no_lamaran', 'like', "%{$search}%");
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get statistics
        $stats = [
            'all' => RecruitmentApplication::count(),
            'menunggu' => RecruitmentApplication::where('status', 'menunggu_seleksi')->count(),
            'seleksi_adm' => RecruitmentApplication::where('status', 'seleksi_administrasi')->count(),
            'tes' => RecruitmentApplication::whereIn('status', ['tes_tertulis', 'lolos_tes', 'tidak_lolos_tes'])->count(),
            'wawancara' => RecruitmentApplication::whereIn('status', ['wawancara', 'lolos_wawancara', 'tidak_lolos_wawancara'])->count(),
            'diterima' => RecruitmentApplication::where('status', 'diterima')->count(),
        ];

        $jobs = RecruitmentJob::where('status', 'aktif')->get();

        return view('recruitment.applications.index', compact('applications', 'stats', 'jobs'));
    }

    public function show(RecruitmentApplication $application)
    {
        $application->load([
            'recruitmentProfile.user',
            'recruitmentProfile.educations',
            'recruitmentProfile.workExperiences',
            'recruitmentProfile.skills',
            'recruitmentJob',
            'stages.recruitmentPipelineStage',
            'stages.penilai'
        ]);

        $interviewers = User::role(['personalia'])->get();

        return view('recruitment.applications.show', compact('application', 'interviewers'));
    }

    /**
     * Update application status and scores.
     */
    public function stages(RecruitmentApplication $application)
    {
        return view('recruitment.applications.stages', compact('application'));
    }

    public function updateStatus(Request $request, RecruitmentApplication $application)
    {
        $validated = $request->validate([
            'status'              => 'required|string',
            'catatan'             => 'nullable|string',
            'skor_administrasi'   => 'nullable|numeric|min:0|max:100',
            'nilai_tes'           => 'nullable|numeric|min:0|max:100',
            'nilai_wawancara'     => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $application->status;

            // Update fields
            $application->status = $validated['status'];
            $application->catatan_rekruter = $validated['catatan'] ?? $application->catatan_rekruter;
            $application->skor_administrasi = $validated['skor_administrasi'] ?? $application->skor_administrasi;
            $application->nilai_tes = $validated['nilai_tes'] ?? $application->nilai_tes;
            $application->nilai_wawancara = $validated['nilai_wawancara'] ?? $application->nilai_wawancara;

            // Hitung nilai_akhir (rata-rata dari nilai yang ada)
            $nilai = array_filter([
                $application->skor_administrasi,
                $application->nilai_tes,
                $application->nilai_wawancara
            ]);
            if (count($nilai) > 0) {
                $application->nilai_akhir = array_sum($nilai) / count($nilai);
            }

            // Jika status final, set selesai_at
            if (in_array($validated['status'], ['diterima', 'ditolak'])) {
                $application->selesai_at = now();
            }

            $application->processed_by = auth()->id();
            $application->diproses_at = now();
            $application->save();

            // Simpan ke tabel stages (riwayat)
            $application->stages()->create([
                // 'nama_tahapan' => 'Update Status',
                'status'       => $application->status,
                'catatan'      => $validated['catatan'] ?? 'Status diupdate dari ' . $oldStatus,
                'penilai_id'   => auth()->id(),
                'nilai'        => $application->nilai_akhir, // simpan nilai akhir di stage
            ]);

            // Kirim notifikasi ke pelamar (gunakan service)
            $user = $application->recruitmentProfile->user;
            $this->notificationService->send($user->id, [
                'module'        => 'recruitment',
                'reference_type' => RecruitmentApplication::class,
                'reference_id'   => $application->id,
                'reference_code' => $application->no_lamaran,
                'type'          => 'info',
                'action'        => 'status_updated',
                'title'         => 'Update Status Lamaran',
                'message'       => "Status lamaran Anda untuk posisi {$application->recruitmentJob->judul} telah berubah menjadi {$application->status}.",
                'data'          => [
                    'old_status' => $oldStatus,
                    'new_status' => $application->status,
                    'job_title'  => $application->recruitmentJob->judul
                ],
                'action_url'    => route('recruitment.applications.show', $application->id),
                'priority'      => 'high',
                'send_email'    => true
            ]);

            DB::commit();

            return redirect()->route('recruitment.applications.show', $application->id)
                ->with('success', 'Status berhasil diupdate.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update status: ' . $e->getMessage());
        }
    }

    /**
     * Send message to applicant.
     */
    public function sendMessage(Request $request, RecruitmentApplication $application)
    {
        $validated = $request->validate([
            'message' => 'required|string'
        ]);

        $user = $application->recruitmentProfile->user;

        $this->notificationService->send($user->id, [
            'module'        => 'recruitment',
            'reference_type' => RecruitmentApplication::class,
            'reference_id'   => $application->id,
            'type'          => 'info',
            'action'        => 'message_from_recruiter',
            'title'         => 'Pesan dari Rekruter',
            'message'       => $validated['message'],
            'action_url'    => route('recruitment.applications.show', $application->id),
            'priority'      => 'high',
            'send_email'    => true,
            'send_whatsapp' => true
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim'
            ]);
        }

        return redirect()->back()->with('success', 'Pesan berhasil dikirim.');
    }

    /**
     * Add note to application.
     */
    public function addNote(Request $request, RecruitmentApplication $application)
    {
        $validated = $request->validate([
            'note' => 'required|string'
        ]);

        $application->catatan_rekruter = $application->catatan_rekruter . "\n[" . now() . "] " . $validated['note'];
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Catatan berhasil ditambahkan'
        ]);
    }

    /**
     * Bulk action on applications.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,export,update_status',
            'ids' => 'required|array',
            'ids.*' => 'exists:recruitment_applications,id',
            'status' => 'required_if:action,update_status|string'
        ]);

        DB::beginTransaction();
        try {
            $applications = RecruitmentApplication::whereIn('id', $validated['ids'])->get();

            foreach ($applications as $application) {
                if ($validated['action'] == 'delete') {
                    $application->delete();
                } elseif ($validated['action'] == 'update_status') {
                    $oldStatus = $application->status;
                    $application->status = $validated['status'];
                    $application->save();

                    // Notify each applicant
                    $this->notificationService->send($application->recruitmentProfile->user_id, [
                        'module' => 'recruitment',
                        'type' => 'info',
                        'action' => 'bulk_status_update',
                        'title' => 'Update Status Massal',
                        'message' => "Status lamaran Anda untuk {$application->recruitmentJob->judul} telah berubah menjadi {$validated['status']}.",
                        'action_url' => route('recruitment.applications.show', $application->id)
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Aksi massal berhasil dilakukan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan aksi massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export applications to Excel.
     */
    public function exportExcel(Request $request)
    {
        $query = RecruitmentApplication::with([
            'recruitmentProfile.user',
            'recruitmentJob'
        ]);

        if ($request->has('job_id')) {
            $query->where('recruitment_job_id', $request->job_id);
        }

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $applications = $query->get();

        // Generate Excel file
        // Implementation depends on package used (Maatwebsite\Excel)

        return response()->json([
            'success' => true,
            'message' => 'Export berhasil',
            'url' => route('recruitment.applications.export-excel.download')
        ]);
    }

    /**
     * Export applications to PDF.
     */
    public function exportPdf(Request $request)
    {
        // PDF export implementation
    }
}