<?php

namespace App\Http\Controllers;

use App\Domain\Exceptions\ActivePermitExistsException;
use App\Domain\Exceptions\QuotaExceededException;
use App\Http\Requests\Dormitory\ApprovePermitRequest;
use App\Http\Requests\Dormitory\RecordReturnRequest;
use App\Http\Requests\Dormitory\RejectPermitRequest;
use App\Http\Requests\Dormitory\StorePermitRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Services\Boarding\LeaveWorkflowService;
use App\Services\DormitoryService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DormitoryPermitController extends Controller
{
    protected DormitoryService $service;

    public function __construct(
        DormitoryService $service,
        private readonly LeaveWorkflowService $leave,
    ) {
        $this->service = $service;
    }

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryPermit::with(['student', 'room', 'mahrom', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        // Token-based direct lookup (QR scan search from index)
        if ($request->filled('token')) {
            $studentId = DormitoryPermit::verifyScanToken($request->token);
            if ($studentId) {
                $permitsFound = DormitoryPermit::with(['student', 'room', 'approvedBy'])
                    ->where('dormitory_id', $asramaUuid)
                    ->where('student_id', $studentId)
                    ->whereNotNull('scan_token')
                    ->get();
                if ($permitsFound->isNotEmpty()) {
                    return redirect()->route('user.asrama.permits.show', [
                        'userId' => $userId,
                        'asramaUuid' => $asramaUuid,
                        'permitUuid' => $permitsFound->first()->id,
                    ])->with('token_found', 'Izin ditemukan untuk token!');
                }
            }

            return back()->with('token_error', 'Token tidak valid atau izin tidak ditemukan di asrama ini.');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('permit_type')) {
            $query->where('permit_type', $request->permit_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('departure_datetime', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->whereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        $permits = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending' => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'pending')->count(),
            'approved' => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'approved')->count(),
            'overdue' => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'overdue')->count(),
        ];

        // Residents for dropdown & quota view
        $residents = \App\Models\DormitoryResident::with('student.mahroms')
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('room_id')
            ->get();

        // Quota information
        $quotaInfo = null;
        if ($dormitory->policy) {
            $pol = is_string($dormitory->policy) ? json_decode($dormitory->policy, true) : $dormitory->policy;
            if (! empty($pol['leave'])) {
                $quotaInfo = $pol['leave'];
            }
        }

        // Ringkasan policy per permit type (untuk badge di header index)
        $policies = \App\Models\DormitoryLeavePolicy::where('dormitory_id', $asramaUuid)
            ->get()
            ->keyBy('permit_type');

        return view('dormitory.permits.index', compact(
            'dormitory', 'permits', 'userId', 'stats', 'activeYear',
            'residents', 'quotaInfo', 'policies'
        ));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Ambil resident aktif
        $residents = \App\Models\DormitoryResident::with('student.mahroms')
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('room_id')
            ->get();

        // Peta izin aktif per-student untuk banner "Santri masih izin" di form create.
        // Key: student_id. Hanya yg status pending/approved/picked_up/overdue.
        $activePermits = DormitoryPermit::where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->whereIn('status', ['pending', 'approved', 'picked_up', 'overdue'])
            ->orderByDesc('departure_datetime')
            ->get()
            ->keyBy('student_id');

        // Daftar kategori izin khusus (dari PermitType.category='special') — untuk label field di view
        $specialPermitTypes = LeaveWorkflowService::getSpecialPermitTypes();

        return view('dormitory.permits.create', compact(
            'dormitory', 'residents', 'userId', 'activeYear',
            'activePermits', 'specialPermitTypes'
        ));
    }

    public function store(StorePermitRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validated();

        // Jika permit_type = sakit, wajib ada StudentHealthPermit yg sudah approved
        if ($data['permit_type'] === 'sakit') {
            $healthPermit = \App\Models\StudentHealthPermit::where('student_id', $data['student_id'])
                ->where('status', 'approved')
                ->where('dormitory_id', $asramaUuid)
                ->whereDate('start_date', '<=', $data['departure_datetime'])
                ->whereDate('end_date', '>=', $data['departure_datetime'])
                ->first();

            if (! $healthPermit) {
                return back()->withInput()->withErrors([
                    'permit_type' => 'Izin sakit hanya bisa diajukan jika ada keterangan sakit dari UKS yang sudah disetujui.',
                ]);
            }
        }

        // Submit through the workflow service — it handles policy check,
        // rules engine, timeline, and quota update atomically.
        try {
            $permit = $this->leave->submit(
                data: $data,
                dormitoryId: $asramaUuid,
                activeYearId: $activeYear->id,
            );
        } catch (QuotaExceededException $e) {
            $message = $e->getMessage();

            // Make the error message more user-friendly for common cases
            if (str_contains($message, 'pulang')) {
                $message = 'Santri sudah mencapai kuota izin pulang untuk periode ini. Anda tidak bisa mengajukan izin pulang lagi sampai periode baru dimulai (bulannya berikutnya).';
            } elseif (strpos($message, 'Kuota telah mencapai batas maksimum') !== false) {
                $message = 'Kuota izin telah mencapai batas maksimum. Silakan ajukan special permission jika sangat mendesak.';
            }

            \Log::warning('Kuota izin habis', [
                'student_id' => $data['student_id'] ?? null,
                'dormitory' => $asramaUuid,
                'reason' => $message,
            ]);

            return back()->withInput()->withErrors([
                'quota' => $message,
            ]);
        } catch (ActivePermitExistsException $e) {
            \Log::warning('Pengajuan izin ditolak — ada izin aktif', [
                'student_id' => $data['student_id'] ?? null,
                'dormitory' => $asramaUuid,
                'existing' => $e->details,
                'reason' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'student_id' => $e->getMessage(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors([
                'permit_type' => $e->getMessage(),
            ]);
        }

        return redirect()->route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Permintaan izin berhasil diajukan.');
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $permit = DormitoryPermit::with(['student.mahroms', 'room', 'mahrom', 'approvedBy', 'creator'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($permitUuid);

        return view('dormitory.permits.show', compact('dormitory', 'permit', 'userId'));
    }

    /** Generate QR image for a permit (public, no auth required). */
    public function qrImage(string $asramaUuid, string $permitUuid): \Symfony\Component\HttpFoundation\Response
    {
        $permit = DormitoryPermit::where('dormitory_id', $asramaUuid)
            ->findOrFail($permitUuid);

        // Ensure scan token exists
        if (! $permit->scan_token) {
            $permit->getOrCreateScanToken();
        }

        $qrPayload = json_encode($permit->qrPayload());
        $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(280)
            ->margin(2)
            ->generate($qrPayload);

        return response($qrImage, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function approve(ApprovePermitRequest $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $data = $request->validated();

        // If admin changed the permit_type at approval time, persist that first.
        if (! empty($data['permit_type'])) {
            $permit = $this->permitRepository->findByUuid($permitUuid);
            if ($permit && $permit->status === 'pending' && $permit->permit_type !== $data['permit_type']) {
                $permit->permit_type = $data['permit_type'];
                $permit->save();
            }
        }

        // Delegate to the workflow service. It re-runs the rules engine,
        // transitions the student status to ON_LEAVE, and emits the timeline event.
        $this->leave->approve(
            permitId: $permitUuid,
            dormitoryId: $asramaUuid,
            note: $data['approval_note'] ?? null,
        );

        return back()->with('success', 'Izin berhasil disetujui dan notifikasi ke wali telah dikirim.');
    }

    public function reject(RejectPermitRequest $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $data = $request->validated();

        $this->leave->reject(
            permitId: $permitUuid,
            dormitoryId: $asramaUuid,
            note: $data['approval_note'] ?? null,
        );

        return back()->with('success', 'Izin ditolak.');
    }

    /**
     * AJAX endpoint: hitung sisa kuota jika izin disetujui.
     * Mengembalikan informasi used / quota / over-quota untuk modal UI.
     */
    public function inspectQuota(Request $request, string $userId, string $asramaUuid)
    {
        $data = $request->validate([
            'student_id' => 'required|string',
            'permit_type' => 'required|string',
            'departure_datetime' => 'required|date',
        ]);

        $permitType = $data['permit_type'];
        $isSpecial = in_array($permitType, LeaveWorkflowService::getSpecialPermitTypes(), true);
        $departure = CarbonImmutable::parse($data['departure_datetime']);

        $info = $this->leave->inspectQuotaForPermit(
            studentId: $data['student_id'],
            dormitoryId: $asramaUuid,
            permitType: $permitType,
            departure: $departure,
            isSpecial: $isSpecial,
        );

        return response()->json([
            'permit_type' => $permitType,
            'is_special' => $isSpecial,
            'used' => $info['used'],
            'quota' => $info['quota'],
            'over' => $info['over'],
            'period' => $info['period'],
            'remaining' => $info['quota'] !== null ? max(0, (int) $info['quota'] - (int) $info['used']) : null,
            'period_label_id' => match ($info['period']) {
                'month' => 'bulan ini',
                'semester' => 'semester ini',
                'year' => 'tahun ini',
                'week' => 'minggu ini',
                default => 'periode ini',
            },
        ]);
    }

    public function returnRecord(RecordReturnRequest $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $data = $request->validated();

        $this->leave->recordReturn(
            permitId: $permitUuid,
            dormitoryId: $asramaUuid,
            actualReturnDatetime: $data['actual_return_datetime'],
        );

        // Catat bahwa permit selesai (bukan overdue)
        return back()->with('success', 'Kedatangan santri berhasil dicatat.');
    }

    /**
     * Print view for a single permit card with QR code.
     */
    public function card(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $permit = DormitoryPermit::with([
            'student',
            'student.currentClassHistory.studyGroup.gradeLevel',
            'room',
        ])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($permitUuid);

        // Permit dapat dicetak ulang selama belum berpending/rejected (misal: kartu hilang)
        if (! in_array($permit->status, ['approved', 'picked_up', 'returned', 'overdue'])) {
            return redirect()->route('user.asrama.permits.show', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
                'permitUuid' => $permit->id,
            ])->with('warning', 'Kartu hanya dapat dicetak untuk izin yang telah aktif.');
        }

        $now = now();

        return view('dormitory.permits.card', compact('dormitory', 'permit', 'userId', 'now'));
    }

    /**
     * Bulk print view for all approved permits.
     */
    public function bulkCard(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryPermit::with([
            'student',
            'student.currentClassHistory.studyGroup.gradeLevel',
            'room',
        ])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        if ($request->filled('status')) {
            $query->whereIn('status', $request->status === 'approved' ? ['approved', 'returned'] : [$request->status]);
        }

        if ($request->filled('permit_type')) {
            $query->where('permit_type', $request->permit_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('departure_datetime', [$request->start_date, $request->end_date]);
        }

        // Permit dapat dicetak ulang selama belum berstatus pending/rejected (mis. kartu hilang)
        $query->whereIn('status', ['approved', 'picked_up', 'returned', 'overdue']);

        $permits = $query->orderByDesc('departure_datetime')->paginate(30)->withQueryString();

        $now = now();

        return view('dormitory.permits.bulk-card', compact('dormitory', 'permits', 'userId', 'now'));
    }

    /**
     * PDF version of a single permit card.
     */
    public function cardPdf(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        try {
            $dormitory = Dormitory::findOrFail($asramaUuid);
            $permit = DormitoryPermit::with([
                'student',
                'student.currentClassHistory.studyGroup.gradeLevel',
                'room',
            ])
                ->where('dormitory_id', $asramaUuid)
                ->findOrFail($permitUuid);

            if (! in_array($permit->status, ['approved', 'picked_up', 'returned', 'overdue'])) {
                return redirect()->route('user.asrama.permits.show', [
                    'userId' => $userId,
                    'asramaUuid' => $asramaUuid,
                    'permitUuid' => $permit->id,
                ])->with('warning', 'Kartu hanya dapat dicetak untuk izin yang telah aktif.');
            }

            $now = now();

            $html = view('dormitory.permits.card-pdf', compact('dormitory', 'permit', 'userId', 'now'))->render();

            $options = (new Options)
                ->set('isRemoteEnabled', false)
                ->set('isHtml5ParserEnabled', true)
                ->set('defaultFont', 'Courier');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, 175, 600]); // 58mm width (≈165pt) with variable height
            $dompdf->render();

            $output = $dompdf->output();
            $filename = 'kartu-izin-'.($permit->student?->name ?? $permit->id).'-'.now()->format('Ymd-His').'.pdf';

            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($output),
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating permit card PDF: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());

            return redirect()->back()->with('error', 'Gagal membuat PDF: '.$e->getMessage());
        }
    }

    /**
     * PDF version of bulk permit cards.
     */
    public function bulkCardPdf(Request $request, string $userId, string $asramaUuid)
    {
        try {
            $dormitory = Dormitory::findOrFail($asramaUuid);
            $activeYear = AcademicYear::where('is_active', true)->first();

            $query = DormitoryPermit::with([
                'student',
                'student.currentClassHistory.studyGroup.gradeLevel',
                'room',
            ])
                ->where('dormitory_id', $asramaUuid)
                ->where('academic_year_id', $activeYear?->id);

            if ($request->filled('status')) {
                $query->whereIn('status', $request->status === 'approved' ? ['approved', 'returned'] : [$request->status]);
            }

            if ($request->filled('permit_type')) {
                $query->where('permit_type', $request->permit_type);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('departure_datetime', [$request->start_date, $request->end_date]);
            }

            $query->whereIn('status', ['approved', 'picked_up', 'returned', 'overdue']);

            $permits = $query->orderByDesc('departure_datetime')->get();

            if ($permits->isEmpty()) {
                return redirect()->back()->with('warning', 'Tidak ada izin yang memenuhi filter untuk dicetak.');
            }

            $now = now();

            $html = view('dormitory.permits.bulk-card-pdf', compact('dormitory', 'permits', 'userId', 'now'))->render();

            $options = (new Options)
                ->set('isRemoteEnabled', false)
                ->set('isHtml5ParserEnabled', true)
                ->set('defaultFont', 'Courier');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, 175, 600]);
            $dompdf->render();

            $output = $dompdf->output();
            $filename = 'cetak-kartu-izin-'.now()->format('Ymd-His').'.pdf';

            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($output),
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating bulk permit cards PDF: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());

            return redirect()->back()->with('error', 'Gagal membuat PDF: '.$e->getMessage());
        }
    }

    // ── Scan & Verify ───────────────────────────────────────────

    /**
     * Scan page for internal use (requires auth).
     */
    public function scan(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $period = in_array($request->query('period'), ['today', 'week', 'month', 'all'], true)
            ? $request->query('period')
            : 'today';

        // Tanggal efektif untuk masing-masing sumber data (mengikuti period, kecuali user pilih date range eksplisit)
        [$fromDate, $toDate] = $this->resolveDateRange($period, $dateFrom, $dateTo);

        $baseQuery = function () use ($asramaUuid, $search) {
            $q = DormitoryPermit::with(['student', 'room', 'mahrom', 'approvedBy'])
                ->where('dormitory_id', $asramaUuid);

            if ($search !== '') {
                $q->where(function ($sq) use ($search) {
                    $sq->whereHas('student', fn ($st) => $st->where('name', 'like', "%{$search}%"));
                });
            }

            return $q;
        };

        // 1. Permit yang belum dipindai (menunggu penjemputan) — status approved dan belum pernah di-scan
        $awaitingScan = $baseQuery()
            ->where('status', 'approved')
            ->whereNull('pickup_scanned_at')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('departure_datetime', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderByDesc('departure_datetime')
            ->get();

        // 2. Sudah dijemput (sudah meninggalkan asrama, menunggu kepulangan) — status picked_up
        $sudahDijemput = $baseQuery()
            ->where('status', 'picked_up')
            ->whereNotNull('pickup_scanned_at')
            ->whereNull('return_scanned_at')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('pickup_scanned_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderByDesc('pickup_scanned_at')
            ->get();

        // 3. Riwayat kepulangan (sudah kembali ke asrama) — status returned
        $riwayatKepulangan = $baseQuery()
            ->where('status', 'returned')
            ->whereNotNull('return_scanned_at')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('actual_return_datetime', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderByDesc('actual_return_datetime')
            ->limit(20)
            ->get();

        $activeFilterCount = ($search !== '' ? 1 : 0) + ($period !== 'today' ? 1 : 0) + ($dateFrom ? 1 : 0) + ($dateTo ? 1 : 0);

        return view('dormitory.permits.scan', compact(
            'dormitory', 'userId', 'awaitingScan', 'sudahDijemput', 'riwayatKepulangan',
            'search', 'dateFrom', 'dateTo', 'period', 'activeFilterCount'
        ));
    }

    /**
     * Process QR scan with state machine: first scan records pickup, second scan records return.
     */
    public function scanStore(Request $request, string $userId, string $asramaUuid)
    {
        if (! $request->user()) {
            return response()->json(['success' => false, 'message' => 'Anda harus terlebih dahulu masuk.'], 401);
        }

        try {
            // Accept either scan_url (URL lengkap dengan query parameter t/token) or token directly.
            // Data penjemput otomatis diambil dari pengajuan izin (companion_name/companion_relation).
            $request->validate([
                'scan_url' => ['nullable', 'string', 'max:2048'],
                'token' => ['nullable', 'string', 'min:10'],
                'note' => ['nullable', 'string', 'max:500'],
            ]);

            $rawInput = $request->filled('scan_url') ? $request->input('scan_url') : $request->input('token');
            $rawInput = trim((string) $rawInput);

            // Strip surrounding quotes ("..." or '...') if user accidentally copied them
            if (strlen($rawInput) >= 2 && (($rawInput[0] === '"' && substr($rawInput, -1) === '"') || ($rawInput[0] === "'" && substr($rawInput, -1) === "'"))) {
                $rawInput = substr($rawInput, 1, -1);
            }

            $token = $this->extractTokenFromUrl($rawInput) ?? $rawInput;
            $token = urldecode($token);
            $token = trim($token);

            if (empty($token)) {
                return $this->scanResponse($request, 'Format scan tidak benar — gunakan scan URL atau masukkan token.', false);
            }

            $studentId = DormitoryPermit::verifyScanToken($token);
            if (! $studentId) {
                \Log::debug('Token verifikasi gagal', ['token_input' => $token]);

                return $this->scanResponse($request, 'Token tidak valid atau izin tidak ditemukan.', false);
            }

            $permit = DormitoryPermit::where('dormitory_id', $asramaUuid)
                ->where('student_id', $studentId)
                ->whereNotNull('scan_token')
                ->first();

            if (! $permit) {
                return $this->scanResponse($request, 'Token tidak cocok dengan izin di asrama ini.', false);
            }

            $user = $request->user();

            // --- First scan: catat penjemputan (status approved/overdue, belum pernah dipindai) ---
            if (($permit->status === 'approved' || $permit->status === 'overdue') && is_null($permit->pickup_scanned_at)) {
                $pickerName = $permit->companion_name ?? '';
                $pickerRelation = $permit->companion_relation ?? '';

                $scanTime = now();
                $permit->pickup_scanned_at = $scanTime;
                $permit->pickup_scanned_by = $user?->id;
                $permit->last_actioned_by = $user?->id;
                $permit->status = 'picked_up';
                $permit->scanned_at = $scanTime;
                $permit->pickup_details = array_merge($permit->pickup_details ?? [], [
                    'mode' => 'manual_scan',
                    'scanned_at' => $scanTime->toDateTimeString(),
                    'scanner_name' => $user?->name ?? 'Anonymous',
                    'picker_name' => $pickerName,
                    'picker_relation' => $pickerRelation,
                    'note' => $request->input('note', ''),
                ]);
                $permit->save();

                $studentName = $permit->student?->name ?? 'Santri';
                $pickerDisplay = $pickerName !== ''
                    ? "oleh {$pickerName}".($pickerRelation !== '' ? ' ('.ucfirst($pickerRelation).')' : '')
                    : 'berhasil dicatat';

                return $this->scanResponse(
                    $request,
                    "Penjemputan {$studentName} {$pickerDisplay} berhasil dicatat. Status sekarang: Sudah Dijemput.",
                    true,
                    ['student_name' => $studentName, 'status' => 'picked_up'],
                    redirect()->route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
                );
            }

            // --- Second scan: catat kepulangan ke asrama (sudah dijemput, belum pulang) ---
            if ($permit->status === 'picked_up' && is_null($permit->return_scanned_at)) {
                $scanTime = now();
                $permit->return_scanned_at = $scanTime;
                $permit->return_scanned_by = $user?->id;
                $permit->actual_return_datetime = $scanTime;
                $permit->last_actioned_by = $user?->id;
                $permit->status = 'returned';
                $permit->return_details = array_merge($permit->return_details ?? [], [
                    'mode' => 'manual_scan',
                    'scanned_at' => $scanTime->toDateTimeString(),
                    'scanner_name' => $user?->name ?? 'Anonymous',
                    'note' => $request->input('note', ''),
                ]);
                $permit->save();

                $studentName = $permit->student?->name ?? 'Santri';

                return $this->scanResponse(
                    $request,
                    "Kepulangan {$studentName} berhasil dicatat via scan.",
                    true,
                    ['student_name' => $studentName, 'status' => 'returned'],
                    redirect()->route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
                );
            }

            // Check if permit already completed (status returned and return scanned)
            if ($permit->status === 'returned' && ! is_null($permit->return_scanned_at)) {
                return $this->scanResponse(
                    $request,
                    'Izin ini telah selesai (santri sudah kembali ke asrama). QR Code tidak lagi berlaku.',
                    true,
                    ['student_name' => $permit->student?->name ?? 'Santri', 'status' => 'returned'],
                    null
                );
            }

            // Status lain tidak memungkinkan pemindaian
            return $this->scanResponse($request, 'Izin ini tidak dapat dipindai pada kondisi saat ini.', false);

        } catch (\Throwable $e) {
            \Log::error('ScanStore error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->scanResponse($request, 'Terjadi kesalahan internal: '.$e->getMessage(), false);
        }
    }

    /**
     * Handle pickup (berangkat) via modal action.
     */
    public function pickup(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $permit = DormitoryPermit::with('student')
            ->where('dormitory_id', $asramaUuid)
            ->where('id', $permitUuid)
            ->first();

        if (! $permit) {
            return response()->json([
                'success' => false,
                'message' => 'Izin tidak ditemukan.',
            ], 404);
        }

        // Hanya bisa pickup jika status approved/overdue dan belum pernah dipickup.
        // Setelah pickup, status menjadi 'picked_up' dan menunggu kepulangan via returnRecord.
        if (($permit->status === 'approved' || $permit->status === 'overdue') && is_null($permit->pickup_scanned_at)) {
            $permit->pickup_scanned_at = now();
            $permit->pickup_scanned_by = $user?->id;
            $permit->last_actioned_by = $user?->id;
            $permit->status = 'picked_up';
            $permit->scanned_at = now();

            $scanTime = now()->toDateTimeString();
            $scannerName = $user?->name ?? 'Anonymous';
            $pickerName = $permit->companion_name ?? '';
            $pickerRelation = $permit->companion_relation ?? '';
            $permit->pickup_details = array_merge($permit->pickup_details ?? [], [
                'mode' => 'manual_pickup',
                'scanned_at' => $scanTime,
                'scanner_name' => $scannerName,
                'picker_name' => $pickerName,
                'picker_relation' => $pickerRelation,
                'note' => $request->input('note') ?? '',
            ]);
            $permit->save();

            $pickerDisplay = $pickerName !== ''
                ? "oleh {$pickerName}".($pickerRelation !== '' ? ' ('.ucfirst($pickerRelation).')' : '')
                : 'berhasil dicatat';

            return response()->json([
                'success' => true,
                'message' => "Penjemputan {$permit->student->name} {$pickerDisplay} berhasil dicatat. Status sekarang: Sudah Dijemput.",
                'status' => 'picked_up',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Izin ini tidak dapat diambil pada kondisi saat ini.',
        ], 422);
    }

    /**
     * Update permit status manually via UI.
     */
    public function updateStatus(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $request->validate([
            'status' => ['required', 'in:approved,overdue,picked_up,returned,rejected'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $permit = DormitoryPermit::with('student')
            ->where('dormitory_id', $asramaUuid)
            ->where('id', $permitUuid)
            ->first();

        if (! $permit) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Izin tidak ditemukan.',
                ], 404);
            }

            return back()->with('error', 'Izin tidak ditemukan.');
        }

        $oldStatus = $permit->status;
        $newStatus = $request->input('status');

        // Validasi transisi status (state machine 2 fase: approved/overdue -> picked_up -> returned)
        $allowedTransitions = [
            'pending' => ['pending', 'approved', 'rejected'],
            'approved' => ['approved', 'overdue', 'rejected', 'picked_up', 'returned'],
            'overdue' => ['overdue', 'rejected', 'picked_up', 'returned'],
            'picked_up' => ['picked_up', 'returned', 'rejected'],
            'returned' => ['returned'],
            'rejected' => ['rejected'],
        ];

        if (! in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Transisi status '$oldStatus' -> '$newStatus' tidak diperbolehkan.",
                ], 422);
            }

            return back()->with('error', "Transisi status '$oldStatus' -> '$newStatus' tidak diperbolehkan.");
        }

        $permit->status = $newStatus;
        $permit->last_actioned_by = $user?->id;

        // Update field spesifik sesuai status
        if ($newStatus === 'returned' && is_null($permit->pickup_scanned_at)) {
            $permit->pickup_scanned_at = now();
            $permit->scanned_at = now();
        }
        if ($newStatus === 'returned') {
            $permit->actual_return_datetime = now();
            $permit->return_scanned_at = now();
        }

        $permit->save();

        $message = "Status izin {$permit->student->name} diubah ke '$newStatus'.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Resolve date range based on period or manual dates.
     *
     * @return array{0:?string,1:?string}
     */
    private function resolveDateRange(string $period, ?string $dateFrom, ?string $dateTo): array
    {
        // Kalau user pilih date range manual, pakai itu
        if ($dateFrom || $dateTo) {
            return [$dateFrom, $dateTo];
        }

        $today = now()->toDateString();

        return match ($period) {
            'today' => [$today, $today],
            'week' => [now()->subDays(6)->toDateString(), $today],
            'month' => [now()->subDays(29)->toDateString(), $today],
            'all' => [null, null],
            default => [$today, $today],
        };
    }

    /**
     * Extract scan token from a full URL.
     *
     * Accepts formats like:
     * - https://alim.id/permits/verify?t=<token>
     * - https://alim.id/permits/verify?token=<token>
     * - https://api.example.com/anything/<token>
     */
    private function extractTokenFromUrl(string $scanUrl): ?string
    {
        $scanUrl = trim($scanUrl);

        // Already a raw token (no scheme, no slashes) — return as-is
        if (! preg_match('/^[a-z]+:\/\//i', $scanUrl) && ! str_contains($scanUrl, '/')) {
            return $scanUrl;
        }

        // Try parse_url for query parameter extraction
        $parts = parse_url($scanUrl);
        if ($parts === false) {
            return null;
        }

        // Extract from query first (?t=... or ?token=...)
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (['t', 'token', 'permit_token', 'scan_token'] as $key) {
                if (! empty($query[$key])) {
                    return $query[$key];
                }
            }
        }

        // Fallback: last path segment if URL is like /verify/<token>
        if (! empty($parts['path'])) {
            $segments = array_values(array_filter(explode('/', $parts['path'])));
            if (! empty($segments)) {
                $last = end($segments);
                // Skip path segments that look like routes (no dots, no '=')
                if (strlen($last) >= 10 && ! str_contains($last, '=')) {
                    return $last;
                }
            }
        }

        return null;
    }

    /**
     * Return JSON for AJAX scans, redirect for HTML form fallback.
     */
    private function scanResponse(Request $request, string $message, bool $success, array $data = [], ?\Illuminate\Http\RedirectResponse $redirect = null)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $payload = array_merge([
                'success' => $success,
                'message' => $message,
            ], $data);

            return response()->json($payload, $success ? 200 : 422);
        }

        if ($success) {
            return $redirect
                ? $redirect->with('success', $message)
                : back()->with('success', $message);
        }

        return back()->withErrors(['token' => $message])->withInput();
    }

    /**
     * Public verify page - accepts token as query param or form input.
     */
    public function verify(Request $request)
    {
        $token = $request->query('t');

        // Jika ada token query, verifikasi otomatis
        if ($token) {
            $studentId = DormitoryPermit::verifyScanToken($token);
            if ($studentId) {
                $permit = DormitoryPermit::where('student_id', $studentId)
                    ->whereNotNull('scan_token')
                    ->first();

                if ($permit) {
                    // Halaman publik hanya untuk informasi — pemindaian hanya boleh dilakukan oleh staf asrama.
                    return view('dormitory.permits.verify', [
                        'permit' => $permit,
                        'success' => 'Tunjukkan halaman ini kepada staf asrama untuk dipindai.',
                    ]);
                }
            }

            // Token invalid atau izin tidak ditemukan
            return view('dormitory.permits.verify', [
                'error' => 'Token tidak valid atau izin tidak ditemukan.',
                'token' => $token,
            ]);
        }

        return view('dormitory.permits.verify', compact('token'));
    }

    /**
     * Handle verify action from both internal scan page and public verify page.
     */
    public function verifyStore(Request $request)
    {
        $request->validate([
            'token' => 'required|string|min:10',
            'action' => 'sometimes|in:pickup:return,return',
        ]);

        $studentId = DormitoryPermit::verifyScanToken($request->token);
        if (! $studentId) {
            return back()->withErrors(['token' => 'Token tidak valid atau izin tidak ditemukan.'])->withInput();
        }

        $permit = DormitoryPermit::where('student_id', $studentId)
            ->whereNotNull('scan_token')
            ->first();

        if (! $permit) {
            return back()->withErrors(['token' => 'Token tidak cocok dengan izin mana pun.'])->withInput();
        }

        if ($request->action === 'return') {
            try {
                $this->leave->recordReturn(
                    permitId: $permit->id,
                    dormitoryId: $permit->dormitory_id,
                    actualReturnDatetime: $request->actual_return_datetime ?? now(),
                );

                return redirect()->route('permits.verify', ['t' => $request->token])
                    ->with('success', 'Santri berhasil ditandai sudah kembali.');
            } catch (\Exception $e) {
                return back()->withErrors(['return' => $e->getMessage()])->withInput();
            }
        }

        // Default: pickup mode (status approved/overdue, belum pernah dipindai)
        if (($permit->status === 'approved' || $permit->status === 'overdue') && is_null($permit->pickup_scanned_at)) {
            $scanTime = now();
            $scannerName = auth()->user()?->name ?? 'Anonymous';
            $permit->update([
                'pickup_scanned_at' => $scanTime,
                'pickup_scanned_by' => auth()->id(),
                'scanned_at' => $scanTime,
                'last_actioned_by' => auth()->id(),
                'status' => 'picked_up',
                'pickup_details' => [
                    'mode' => 'web',
                    'scanned_at' => $scanTime->toDateTimeString(),
                    'scanner_name' => $scannerName,
                ],
            ]);

            return redirect()->route('permits.verify', ['t' => $request->token])
                ->with('success', 'Penjemputan berhasil dicatat. Status sekarang: Sudah Dijemput.');
        }

        return back()->withErrors(['token' => 'Izin ini tidak dapat dipindai pada kondisi saat ini.'])->withInput();
    }

    /**
     * Public verify by token — direct URL open from mobile/camera scan.
     * Handles both first scan (pickup) and second scan (return) automatically based on current status.
     */
    public function publicVerify(string $token)
    {
        $studentId = DormitoryPermit::verifyScanToken($token);

        if (! $studentId) {
            return view('dormitory.permits.verify', [
                'token' => null,
                'error' => 'Token tidak valid. Izin tidak ditemukan.',
            ]);
        }

        $permit = DormitoryPermit::where('student_id', $studentId)
            ->whereNotNull('scan_token')
            ->first();

        if (! $permit) {
            return view('dormitory.permits.verify', [
                'token' => null,
                'error' => 'Token tidak cocok dengan data izin.',
            ]);
        }

        // Halaman publik hanya untuk informasi — pemindaian hanya boleh dilakukan oleh staf asrama.
        return view('dormitory.permits.verify', compact('permit'), [
            'success' => 'Tunjukkan halaman ini kepada staf asrama untuk dipindai.',
        ]);
    }
}
