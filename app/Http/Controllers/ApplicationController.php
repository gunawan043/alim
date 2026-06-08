<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJob;
use App\Models\User;
use App\Models\RecruitmentProfile;
use App\Services\NotificationUniversalService;
use App\Services\RecruitmentNotificationService;
use App\Services\CandidateConversionService;
use App\Services\RecruitmentDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function index(Request $request, string $userId)
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

        // Handle tab filter (maps tab names to status arrays)
        $tab = $request->get('tab');
        if ($tab) {
            $tabMap = [
                'menunggu' => ['menunggu_seleksi'],
                'seleksi_adm' => ['seleksi_administrasi'],
                'tes' => ['tes_tertulis', 'lolos_tes', 'tidak_lolos_tes', 'lolos_administrasi'],
                'diterima' => ['diterima'],
                'ditolak' => ['ditolak'],
                'ditolak' => ['ditolak', 'tidak_lolos_administrasi', 'tidak_lolos_tes', 'tidak_lolos_wawancara'],
            ];
            if (isset($tabMap[$tab])) {
                $query->whereIn('status', $tabMap[$tab]);
            }
        }

        // Filter by status (direct filter)
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

        return view('recruitment.applications.index', compact('applications', 'stats', 'jobs', 'userId'));
    }

    public function show(string $userId, RecruitmentApplication $application)
    {
        $application->load([
            'recruitmentProfile.user',
            'recruitmentProfile.educations',
            'recruitmentProfile.workExperiences',
            'recruitmentProfile.skills',
            'recruitmentProfile.trainings',
            'recruitmentProfile.documents',
            'recruitmentJob',
            'stages.recruitmentPipelineStage',
            'stages.penilai'
        ]);

        $interviewers = User::role(['personalia'])->get();

        return view('recruitment.applications.show', compact('application', 'interviewers', 'userId'));
    }

    /**
     * Update application status and scores.
     */
    public function stages(string $userId, RecruitmentApplication $application)
    {
        return view('recruitment.applications.stages', compact('application', 'userId'));
    }

    /**
     * Update nilai aplikasi (nilai tes, wawancara, praktikum, detail penilaian)
     * dan push ke recruitment.abuhurairah.id
     */
    public function updateNilai(Request $request, string $userId, RecruitmentApplication $application)
    {
        $validated = $request->validate([
            'skor_administrasi'   => 'nullable|numeric|min:0|max:100',
            'nilai_tes'           => 'nullable|numeric|min:0|max:100',
            'nilai_wawancara'     => 'nullable|numeric|min:0|max:100',
            'nilai_praktikum'     => 'nullable|numeric|min:0|max:100',
            'ranking'             => 'nullable|integer|min:0',
            'status_akhir'        => 'nullable|string',
            'detail_penilaian'    => 'nullable|array',
            'detail_penilaian.komunikasi'     => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.attitude'       => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.teknis'         => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.leadership'     => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.teamwork'      => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.motivasi'      => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.appearance'    => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.pengalaman'    => 'nullable|numeric|min:0|max:100',
            'catatan_penilaian'               => 'nullable|string|max:1000',
        ]);

        try {
            // Update ke database lokal dulu
            $application->fill($validated);
            $application->save();

            // Push ke recruitment.abuhurairah.id (non-blocking failure)
            $syncResult = RecruitmentDocumentService::pushNilaiToRecruitment(
                $application->id,
                array_merge($validated, ['detail_penilaian' => $validated['detail_penilaian'] ?? []])
            );

            if (!$syncResult) {
                // Log warning tapi jangan fail request - data lokal sudah tersimpan
                logger()->warning('Failed to push nilai to recruitment API, but local DB updated', [
                    'application_id' => $application->id,
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Nilai berhasil diperbarui.' . ($syncResult ? '' : ' (Peringatan: sinkronisasi ke recruitment.abuhurairah.id gagal)'));
        } catch (\Exception $e) {
            logger()->error('Failed to update nilai', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui nilai: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, string $userId, RecruitmentApplication $application)
    {
        $validated = $request->validate([
            'status'              => 'required|string',
            'catatan'             => 'nullable|string',
            'skor_administrasi'   => 'nullable|numeric|min:0|max:100',
            'nilai_tes'           => 'nullable|numeric|min:0|max:100',
            'nilai_wawancara'     => 'nullable|numeric|min:0|max:100',
            'nilai_praktikum'     => 'nullable|numeric|min:0|max:100',
            'detail_penilaian'    => 'nullable|array',
            'detail_penilaian.komunikasi'     => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.attitude'       => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.teknis'         => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.leadership'     => 'nullable|numeric|min:0|max:100',
            'detail_penilaian.problem_solving'=> 'nullable|numeric|min:0|max:100',
            'detail_penilaian.kerjasama_tim'  => 'nullable|numeric|min:0|max:100',
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
            $application->nilai_praktikum = $validated['nilai_praktikum'] ?? $application->nilai_praktikum;

            // Simpan detail penilaian per-kriteria (JSON) — drop nilai kosong
            if ($request->has('detail_penilaian') && is_array($request->detail_penilaian)) {
                $detail = array_filter(
                    $request->detail_penilaian,
                    fn ($v) => $v !== null && $v !== ''
                );
                $application->detail_penilaian = !empty($detail) ? json_encode($detail) : null;
            }

            // Hitung nilai_akhir (rata-rata dari nilai yang ada)
            $nilai = array_filter([
                $application->skor_administrasi,
                $application->nilai_tes,
                $application->nilai_wawancara,
                $application->nilai_praktikum,
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
                'status'       => $application->status,
                'catatan'      => $validated['catatan'] ?? 'Status diupdate dari ' . $oldStatus,
                'penilai_id'   => auth()->id(),
                'nilai'        => $application->nilai_akhir,
            ]);

            // Kirim notifikasi ke pelamar via applicant app (EMAIL)
            // Ini akan di-queue dan diproses async oleh applicant app
            $user = $application->recruitmentProfile->user;

            // 1. Kirim notifikasi internal (database notification)
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
                    'job_title'  => $application->recruitmentJob->judul,
                    'detail_penilaian' => $application->detail_penilaian,
                ],
                'action_url'    => route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]),
                'priority'      => 'high',
                'send_email'    => true
            ]);

            // 2. Kirim notifikasi email ke applicant via recruitment app
            RecruitmentNotificationService::notifyApplicationStatusChanged(
                $application,
                $oldStatus,
                $application->status,
                $validated['catatan'] ?? null
            );

            DB::commit();

            return redirect()->route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id])
                ->with('success', 'Status berhasil diupdate dan notifikasi email telah dikirim.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update status: ' . $e->getMessage());
        }
    }

    /**
     * Send message to applicant.
     */
    public function sendMessage(Request $request, string $userId, RecruitmentApplication $application)
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
            'action_url'    => route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]),
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
    public function addNote(Request $request, string $userId, RecruitmentApplication $application)
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
    public function bulkAction(Request $request, string $userId)
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

                    // Notify each applicant via applicant app
                    RecruitmentNotificationService::notifyApplicationStatusChanged(
                        $application,
                        $oldStatus,
                        $validated['status'],
                        null
                    );
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
    public function exportExcel(Request $request, string $userId)
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
            'url' => route('user.ats.applications.export-excel', ['userId' => $userId])
        ]);
    }

    /**
     * Export applications to PDF.
     */
    public function exportPdf(Request $request, string $userId)
    {
        // PDF export implementation
    }

    /**
     * Mass announcement for administrative selection results.
     * Send notifications to all specified candidates about their admin selection status.
     */
    public function announceAdminResults(Request $request, string $userId)
    {
        $validated = $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:recruitment_applications,id',
        ]);

        DB::beginTransaction();
        try {
            $applications = RecruitmentApplication::with('recruitmentProfile.user', 'recruitmentJob')
                ->whereIn('id', $validated['application_ids'])
                ->whereIn('status', ['lolos_administrasi', 'tidak_lolos_administrasi'])
                ->get();

            $sent = 0;
            foreach ($applications as $app) {
                $isLolos = $app->status === 'lolos_administrasi';
                $statusText = $isLolos ? 'LULOS SELEKSI ADMINISTRASI' : 'TIDAK LULOS SELEKSI ADMINISTRASI';

                $this->notificationService->send($app->recruitmentProfile->user_id, [
                    'module' => 'recruitment',
                    'reference_type' => RecruitmentApplication::class,
                    'reference_id' => $app->id,
                    'reference_code' => $app->no_lamaran,
                    'type' => $isLolos ? 'success' : 'error',
                    'action' => 'hasil_seleksi_administrasi',
                    'title' => 'Pengumuman Hasil Seleksi Administrasi',
                    'message' => "Hasil seleksi administrasi untuk posisi {$app->recruitmentJob->judul}: {$statusText}",
                    'data' => [
                        'posisi' => $app->recruitmentJob->judul,
                        'status' => $statusText,
                        'no_lamaran' => $app->no_lamaran,
                    ],
                    'action_url' => route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]),
                    'priority' => 'high',
                    'send_email' => true,
                    'send_whatsapp' => true,
                ]);

                $sent++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Pengumuman berhasil dikirim ke {$sent} kandidat"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pengumuman: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Konversi pelamar yang diterima menjadi GTK/pegawai.
     */
    public function convertToEmployee(Request $request, string $userId, RecruitmentApplication $application)
    {
        if ($request->isMethod('get')) {
            $application->load(['recruitmentProfile.user', 'recruitmentJob']);
            $workUnits = \App\Models\WorkUnit::orderBy('name')->get();
            return view('recruitment.applications.convert', compact('application', 'userId', 'workUnits'));
        }

        $validated = $request->validate([
            'jenis_gtk'        => 'required|in:guru,tendik,staf,kopf',
            'status_kepegawaian' => 'required|in:tetap,kontrak,probation,magang,honor',
            'unit_kerja'       => 'required|string|max:150',
            'jabatan'          => 'required|string|max:100',
            'tmt'              => 'required|date',
            'penempatan'      => 'nullable|string|max:200',
            'kontrak_jenis'    => 'nullable|string|max:50',
            'kontrak_berakhir' => 'nullable|date',
            'durasi_bulan'     => 'nullable|integer|min:1|max:60',
            'gaji_pokok'       => 'nullable|numeric|min:0',
            'tunjangan_tetap'  => 'nullable|numeric|min:0',
            'tunjangan_tidak_tetap' => 'nullable|numeric|min:0',
            'catatan'          => 'nullable|string|max:500',
        ]);

        try {
            (new \App\Services\CandidateConversionService)->convert($application, $validated);

            return redirect()
                ->route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id])
                ->with('success', 'Pelamar berhasil dikonversi menjadi GTK.');

        } catch (\Exception $e) {
            \Log::error('Konversi pelamar gagal', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Gagal mengkonversi: ' . $e->getMessage())
                ->withInput();
        }
    }
}