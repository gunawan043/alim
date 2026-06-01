<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentApplicationStage;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJob;
use App\Models\User;
use App\Services\NotificationUniversalService;
use App\Services\RecruitmentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class InterviewController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display exam/test results page.
     */
    public function index(Request $request, string $userId)
    {
        // Ambil lowongan aktif untuk filter
        $jobs = RecruitmentJob::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil kandidat yang LOOS ADMINISTRASI (siap mengikuti tes)
        $query = RecruitmentApplication::with([
            'recruitmentProfile.user',
            'recruitmentJob',
            'stages.recruitmentPipelineStage',
        ])->where('status', 'lolos_administrasi');

        // Filter by job
        if ($request->filled('job_id')) {
            $query->where('recruitment_job_id', $request->job_id);
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('recruitmentProfile.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $applications = $query->orderBy('tanggal_melamar', 'desc')->get();

        // Statistik
        $totalKandidat = $applications->count();
        $totalDiterima = $applications->filter(fn($a) => $a->status_akhir === 'diterima')->count();
        $totalDitolak  = $applications->filter(fn($a) => $a->status_akhir === 'ditolak')->count();
        $totalCadangan = $applications->filter(fn($a) => $a->status_akhir === 'cadangan')->count();

        // Ambil data hari tes dari record pertama (jika ada)
        $hariTes = null;
        $lokasiTes = null;
        if ($applications->isNotEmpty()) {
            $firstApp = $applications->first();
            $hariTesDate = $firstApp->stages->first()?->jadwal_mulai;
            $hariTes = $hariTesDate ? $hariTesDate->format('d M Y') : null;
            $lokasiTes = $firstApp->stages->first()?->lokasi;
        }

        return view('recruitment.interviews.index', compact(
            'applications', 'jobs', 'userId',
            'totalKandidat', 'totalDiterima', 'totalDitolak', 'totalCadangan',
            'hariTes', 'lokasiTes'
        ));
    }

    /**
     * Get calendar events.
     */
    public function calendarEvents(Request $request, string $userId)
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
    public function store(Request $request, string $userId)
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

            // Prepare stage data for notification
            $stageData = [
                'stage_type' => $validated['stage_name'],
                'schedule_date' => $validated['jadwal_mulai'],
                'schedule_time' => date('H:i', strtotime($validated['jadwal_mulai'])),
                'lokasi' => $validated['lokasi'] ?? 'Akan diinformasikan',
                'catatan' => $validated['catatan'] ?? null,
            ];

            // Notify candidate (database notification)
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
                'action_url' => route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]),
                'priority' => 'high',
                'send_email' => true,
                'send_whatsapp' => true
            ]);

            // Send EMAIL notification via recruitment app
            RecruitmentNotificationService::notifyInterviewScheduled($application, $stageData);

            // Notify interviewer
            if ($validated['penilai_id']) {
                $this->notificationService->send($validated['penilai_id'], [
                    'module' => 'recruitment',
                    'type' => 'info',
                    'action' => 'interview_assigned',
                    'title' => 'Jadwal Interview',
                    'message' => "Anda ditugaskan sebagai penilai untuk {$application->recruitmentProfile->user->name} pada " . date('d M Y H:i', strtotime($validated['jadwal_mulai'])),
                    'action_url' => route('user.ats.interviews.show', ['userId' => $userId, 'interview' => $stage->id]),
                    'priority' => 'high'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal interview berhasil disimpan dan notifikasi email telah dikirim',
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
    public function show(string $userId, RecruitmentApplicationStage $interview)
    {
        $interview->load([
            'recruitmentApplication.recruitmentProfile.user',
            'recruitmentApplication.recruitmentJob',
            'penilai'
        ]);

        return view('recruitment.interviews.show', compact('interview', 'userId'));
    }

    /**
     * Reschedule interview.
     */
    public function reschedule(Request $request, string $userId, RecruitmentApplicationStage $interview)
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

        // Prepare stage data for notification
        $stageData = [
            'stage_type' => $interview->recruitmentPipelineStage->nama_tahapan,
            'schedule_date' => $validated['jadwal_mulai'],
            'schedule_time' => date('H:i', strtotime($validated['jadwal_mulai'])),
            'lokasi' => $interview->lokasi ?? 'Akan diinformasikan',
            'catatan' => 'Jadwal diubah dari ' . $oldDate . '. Alasan: ' . $validated['alasan'],
        ];

        // Notify candidate (database notification)
        $this->notificationService->send($interview->recruitmentApplication->recruitmentProfile->user_id, [
            'module' => 'recruitment',
            'type' => 'warning',
            'action' => 'interview_rescheduled',
            'title' => 'Jadwal Interview Diubah',
            'message' => "Jadwal {$interview->recruitmentPipelineStage->nama_tahapan} Anda telah diubah dari {$oldDate} menjadi " . date('d M Y H:i', strtotime($validated['jadwal_mulai'])),
            'action_url' => route('user.ats.applications.show', ['userId' => $userId, 'application' => $interview->recruitmentApplication->id]),
            'priority' => 'high',
            'send_email' => true
        ]);

        // Send EMAIL notification via recruitment app
        RecruitmentNotificationService::notifyInterviewRescheduled($interview->recruitmentApplication, $stageData);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil diubah dan notifikasi email telah dikirim'
        ]);
    }

    /**
     * Mark interview as complete and add feedback.
     */
    public function markComplete(Request $request, string $userId, RecruitmentApplicationStage $interview)
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

            // Prepare stage data for notification
            $stageData = [
                'stage_type' => $stageName,
                'schedule_date' => $interview->jadwal_mulai,
                'schedule_time' => date('H:i', strtotime($interview->jadwal_mulai)),
                'lokasi' => $interview->lokasi ?? null,
            ];

            // Notify candidate (database notification)
            $statusText = $validated['hasil'] == 'lolos' ? 'Lolos' : 'Tidak Lolos';
            $this->notificationService->send($application->recruitmentProfile->user_id, [
                'module' => 'recruitment',
                'type' => $validated['hasil'] == 'lolos' ? 'success' : 'error',
                'action' => 'interview_result',
                'title' => 'Hasil ' . $stageName,
                'message' => "Hasil {$stageName} Anda: {$statusText}" . ($validated['feedback'] ? "\nCatatan: {$validated['feedback']}" : ''),
                'action_url' => route('user.ats.applications.show', ['userId' => $userId, 'application' => $application->id]),
                'priority' => 'high',
                'send_email' => true
            ]);

            // Send EMAIL notification via recruitment app
            RecruitmentNotificationService::notifyStageResult(
                $application,
                $validated['hasil'],
                $stageData,
                $validated['feedback'] ?? null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hasil interview berhasil disimpan dan notifikasi email telah dikirim'
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
    public function addFeedback(Request $request, string $userId, RecruitmentApplicationStage $interview)
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

    /**
     * Bulk save all test results.
     */
    public function saveAllResults(Request $request, string $userId)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.application_id' => 'required|exists:recruitment_applications,id',
            'results.*.nilai_tes_tulis' => 'nullable|numeric|min:0|max:100',
            'results.*.nilai_tes_praktikum' => 'nullable|numeric|min:0|max:100',
            'results.*.nilai_wawancara' => 'nullable|numeric|min:0|max:100',
            'results.*.status_akhir' => 'required|in:diterima,ditolak,cadangan,menunggu',
            'results.*.catatan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['results'] as $result) {
                $app = RecruitmentApplication::find($result['application_id']);

                $nilaiTes = $result['nilai_tes_tulis'];
                $nilaiPrak = $result['nilai_tes_praktikum'];
                $nilaiWaw = $result['nilai_wawancara'];

                // Hitung rata-rata dari nilai yang ada
                $values = array_filter([$nilaiTes, $nilaiPrak, $nilaiWaw]);
                $rataRata = count($values) > 0 ? array_sum($values) / count($values) : null;

                $app->nilai_tes = $nilaiTes;
                $app->nilai_wawancara = $nilaiWaw;
                $app->nilai_akhir = $rataRata;

                // Set final status
                $app->status_akhir = $result['status_akhir'];
                $app->catatan_rekruter = $result['catatan'] ?? $app->catatan_rekruter;

                if ($result['status_akhir'] === 'diterima') {
                    $app->status = 'diterima';
                    $app->selesai_at = now();
                } elseif ($result['status_akhir'] === 'ditolak') {
                    $app->status = 'ditolak';
                    $app->selesai_at = now();
                }

                $app->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hasil seleksi berhasil disimpan untuk ' . count($validated['results']) . ' kandidat'
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
     * Announce final results to all candidates with decided status.
     */
    public function announceAll(Request $request, string $userId)
    {
        $validated = $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:recruitment_applications,id',
        ]);

        DB::beginTransaction();
        try {
            $applications = RecruitmentApplication::with('recruitmentProfile.user')
                ->whereIn('id', $validated['application_ids'])
                ->whereIn('status_akhir', ['diterima', 'ditolak', 'cadangan'])
                ->get();

            $sent = 0;
            foreach ($applications as $app) {
                $statusText = match ($app->status_akhir) {
                    'diterima' => 'DITERIMA',
                    'ditolak' => 'TIDAK DITERIMA',
                    'cadangan' => 'CADANGAN',
                    default => 'BELUM DIPTUTKAN',
                };

                $this->notificationService->send($app->recruitmentProfile->user_id, [
                    'module' => 'recruitment',
                    'type' => $app->status_akhir === 'diterima' ? 'success' : ($app->status_akhir === 'ditolak' ? 'error' : 'info'),
                    'action' => 'hasil_seleksi_akhir',
                    'title' => 'Pengumuman Hasil Seleksi Akhir',
                    'message' => "Hasil seleksi akhir untuk posisi {$app->recruitmentJob->judul}: {$statusText}",
                    'data' => [
                        'posisi' => $app->recruitmentJob->judul,
                        'status' => $statusText,
                        'nilai_akhir' => $app->nilai_akhir,
                    ],
                    'action_url' => route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]),
                    'priority' => 'high',
                    'send_email' => true,
                    'send_whatsapp' => true,
                ]);

                RecruitmentNotificationService::notifyStageResult($app, $app->status_akhir, [
                    'stage_type' => 'Seleksi Akhir',
                    'location' => null,
                ], $app->catatan_rekruter);

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
     * Export results to Excel.
     */
    public function exportResults(Request $request, string $userId)
    {
        $query = RecruitmentApplication::with([
            'recruitmentProfile.user',
            'recruitmentJob',
        ])->where('status', 'lolos_administrasi');

        if ($request->filled('job_id')) {
            $query->where('recruitment_job_id', $request->job_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('recruitmentProfile.user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $applications = $query->orderBy('nilai_akhir', 'desc')->get();

        $data = $applications->map(fn($app, $i) => [
            'No' => $i + 1,
            'Nama Kandidat' => $app->recruitmentProfile->user->name,
            'Posisi' => $app->recruitmentJob->judul,
            'Nilai Tes Tulis' => $app->nilai_tes ?? '-',
            'Nilai Tes Praktikum' => $app->nilai_praktikum ?? '-',
            'Nilai Wawancara' => $app->nilai_wawancara ?? '-',
            'Nilai Rata-rata' => $app->nilai_akhir ? round($app->nilai_akhir, 2) : '-',
            'Status Akhir' => match ($app->status_akhir) {
                'diterima' => 'DITERIMA',
                'ditolak' => 'TIDAK DITERIMA',
                'cadangan' => 'CADANGAN',
                default => 'MENUNGGU',
            },
            'Catatan' => $app->catatan_rekruter ?? '-',
        ]);

        return Excel::download(
            new \App\Exports\GenericExport($data->toArray()),
            'hasil-seleksi-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ============================================================
    // DATA NILAI — Rekap nilai seluruh pelamar
    // ============================================================

    public function dataNilai(Request $request, string $userId)
    {
        $jobs = \App\Models\RecruitmentJob::orderBy('judul')->get();
        $stages = \App\Models\RecruitmentPipelineStage::orderBy('urutan')->get();

        $stats = [
            'total_pelamar' => \App\Models\RecruitmentApplication::whereNull('deleted_at')->count(),
            'pelamar_aktif' => \App\Models\RecruitmentApplication::whereNull('deleted_at')
                ->whereHas('recruitmentJob', fn($q) => $q->where('status', 'aktif'))->count(),
            'pelamar_arsip' => \App\Models\RecruitmentApplication::whereNull('deleted_at')
                ->whereHas('recruitmentJob', fn($q) => $q->whereIn('status', ['ditutup', 'dibatalkan']))->count(),
            'sudah_dinilai' => \App\Models\RecruitmentApplication::whereNull('deleted_at')
                ->whereNotNull('nilai_akhir')->count(),
            'belum_dinilai' => \App\Models\RecruitmentApplication::whereNull('deleted_at')
                ->whereNull('nilai_akhir')->count(),
            'lulus_seleksi' => \App\Models\RecruitmentApplication::whereNull('deleted_at')
                ->where('status_akhir', 'lulus')->count(),
        ];

        return view('ats.data-nilai.index', compact('userId', 'jobs', 'stages', 'stats'));
    }

    public function dataNilaiDatatable(Request $request, string $userId)
    {
        $query = \App\Models\RecruitmentApplication::with(['profile', 'recruitmentJob', 'stages.stage'])
            ->whereNull('deleted_at')
            ->when($request->job_id,        fn($q, $v) => $q->where('recruitment_job_id', $v))
            ->when($request->status,        fn($q, $v) => $q->where('status', $v))
            ->when($request->status_akhir,  fn($q, $v) => $q->where('status_akhir', $v))
            ->when($request->recruitment_status, function ($q, $v) {
                if ($v === 'aktif') {
                    $q->whereHas('recruitmentJob', fn($qq) => $qq->where('status', 'aktif'));
                } elseif ($v === 'arsip') {
                    $q->whereHas('recruitmentJob', fn($qq) => $qq->whereIn('status', ['ditutup', 'dibatalkan']));
                }
            })
            ->when($request->stage_id,      function ($q, $v) {
                $q->whereHas('stages', fn($qq) => $qq->where('recruitment_pipeline_stage_id', $v));
            })
            ->when($request->q, function ($q, $kw) {
                $q->where(function ($qq) use ($kw) {
                    $qq->where('no_lamaran', 'like', "%$kw%")
                       ->orWhereHas('profile', fn($pp) => $pp->where('nama_lengkap', 'like', "%$kw%"));
                });
            })
            ->when($request->nilai_min !== null && $request->nilai_min !== '', fn($q) => $q->where('nilai_akhir', '>=', $request->nilai_min))
            ->when($request->nilai_max !== null && $request->nilai_max !== '', fn($q) => $q->where('nilai_akhir', '<=', $request->nilai_max));

        return datatables()->of($query)
            ->addColumn('pelamar', function ($r) {
                $nama = $r->profile->nama_lengkap ?? '-';
                $no   = $r->no_lamaran;
                $foto = $r->profile->foto_path ?? null;
                $initial = strtoupper(substr($nama, 0, 1));
                $avatar  = $foto
                    ? '<img src="' . asset('storage/' . $foto) . '" class="rounded-circle" width="32" height="32" style="object-fit:cover">'
                    : '<div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold" style="width:32px;height:32px;font-size:.8rem">' . $initial . '</div>';
                return '<div class="d-flex align-items-center gap-2">' . $avatar
                    . '<div><div class="fw-semibold">' . e($nama) . '</div>'
                    . '<small class="text-muted">' . e($no) . '</small></div></div>';
            })
            ->addColumn('posisi', function ($r) {
                $status = $r->recruitmentJob->status ?? 'draft';
                $badge = match($status) {
                    'aktif'        => '<span class="badge bg-success-subtle text-success" style="font-size:.65rem"><i class="ri-play-circle-line"></i> Aktif</span>',
                    'ditutup'      => '<span class="badge bg-secondary-subtle text-secondary" style="font-size:.65rem"><i class="ri-stop-circle-line"></i> Ditutup</span>',
                    'dibatalkan'   => '<span class="badge bg-danger-subtle text-danger" style="font-size:.65rem"><i class="ri-close-circle-line"></i> Batal</span>',
                    default        => '<span class="badge bg-light text-muted" style="font-size:.65rem">Draft</span>',
                };
                return '<div class="fw-medium">' . e($r->recruitmentJob->judul ?? '-') . '</div><small class="text-muted">' . $badge . '</small>';
            })
            ->addColumn('skor_administrasi', fn($r) => $r->skor_administrasi !== null ? number_format($r->skor_administrasi, 2) : '<span class="text-muted">-</span>')
            ->addColumn('nilai_tes', fn($r) => $r->nilai_tes !== null ? number_format($r->nilai_tes, 2) : '<span class="text-muted">-</span>')
            ->addColumn('nilai_wawancara', fn($r) => $r->nilai_wawancara !== null ? number_format($r->nilai_wawancara, 2) : '<span class="text-muted">-</span>')
            ->addColumn('nilai_praktikum', fn($r) => $r->nilai_praktikum !== null ? number_format($r->nilai_praktikum, 2) : '<span class="text-muted">-</span>')
            ->addColumn('nilai_akhir', function ($r) {
                if ($r->nilai_akhir === null) {
                    return '<span class="badge bg-light text-muted">Belum</span>';
                }
                $nilai = (float) $r->nilai_akhir;
                $color = $nilai >= 80 ? 'success' : ($nilai >= 60 ? 'warning' : 'danger');
                return '<span class="badge bg-' . $color . '-subtle text-' . $color . ' fw-semibold fs-6">'
                     . number_format($nilai, 2) . '</span>';
            })
            ->addColumn('ranking', function ($r) {
                if ($r->ranking === null) return '<span class="text-muted">-</span>';
                $medal = $r->ranking == 1 ? '🥇' : ($r->ranking == 2 ? '🥈' : ($r->ranking == 3 ? '🥉' : ''));
                return '<span class="fw-bold">' . $medal . ' #' . $r->ranking . '</span>';
            })
            ->addColumn('status_akhir_badge', function ($r) {
                return match($r->status_akhir) {
                    'lulus'         => '<span class="badge bg-success-subtle text-success"><i class="ri-check-line"></i> Lulus</span>',
                    'tidak_lulus'   => '<span class="badge bg-danger-subtle text-danger"><i class="ri-close-line"></i> Tidak Lulus</span>',
                    'cadangan'      => '<span class="badge bg-warning-subtle text-warning"><i class="ri-time-line"></i> Cadangan</span>',
                    default         => '<span class="badge bg-light text-muted">Proses</span>',
                };
            })
            ->addColumn('aksi', function ($r) use ($userId) {
                $urlShow = route('user.ats.applications.show', ['userId' => $userId, 'application' => $r->id]);
                $urlEdit = route('user.ats.applications.edit', ['userId' => $userId, 'application' => $r->id]);
                return '<div class="d-flex gap-1 justify-content-center">'
                    . '<a href="' . $urlShow . '" class="btn btn-soft-primary btn-sm" title="Detail"><i class="ri-eye-line"></i></a>'
                    . '<a href="' . $urlEdit . '" class="btn btn-soft-warning btn-sm" title="Edit Nilai"><i class="ri-edit-2-line"></i></a>'
                    . '</div>';
            })
            ->rawColumns(['pelamar', 'posisi', 'skor_administrasi', 'nilai_tes', 'nilai_wawancara', 'nilai_praktikum', 'nilai_akhir', 'ranking', 'status_akhir_badge', 'aksi'])
            ->make(true);
    }

    public function dataNilaiExport(Request $request, string $userId)
    {
        $query = \App\Models\RecruitmentApplication::with(['profile', 'recruitmentJob'])
            ->whereNull('deleted_at')
            ->when($request->job_id,       fn($q, $v) => $q->where('recruitment_job_id', $v))
            ->when($request->status,       fn($q, $v) => $q->where('status', $v))
            ->when($request->status_akhir, fn($q, $v) => $q->where('status_akhir', $v))
            ->when($request->nilai_min,    fn($q)     => $q->where('nilai_akhir', '>=', $request->nilai_min))
            ->when($request->nilai_max,    fn($q)     => $q->where('nilai_akhir', '<=', $request->nilai_max))
            ->orderBy('nilai_akhir', 'desc')
            ->get();

        $filename = 'data-nilai-' . date('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No Lamaran', 'Nama Pelamar', 'Posisi', 'Skor Administrasi', 'Nilai Tes', 'Nilai Wawancara', 'Nilai Praktikum', 'Nilai Akhir', 'Ranking', 'Status Akhir'], ';');
            foreach ($query as $r) {
                fputcsv($out, [
                    $r->no_lamaran,
                    $r->profile->nama_lengkap ?? '-',
                    $r->recruitmentJob->judul ?? '-',
                    $r->skor_administrasi,
                    $r->nilai_tes,
                    $r->nilai_wawancara,
                    $r->nilai_praktikum,
                    $r->nilai_akhir,
                    $r->ranking,
                    $r->status_akhir,
                ], ';');
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}