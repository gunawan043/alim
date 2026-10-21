<?php

namespace App\Http\Controllers;

use App\Domain\Exceptions\QuotaExceededException;
use App\Http\Requests\Dormitory\ApproveVisitLogRequest;
use App\Http\Requests\Dormitory\RejectVisitLogRequest;
use App\Http\Requests\Dormitory\StoreVisitRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryVisitLog;
use App\Models\DormitoryStaffAssignment;
use App\Support\UserJabatan;
use App\Models\Student;
use App\Services\Boarding\VisitWorkflowService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class DormitoryVisitLogController extends Controller
{
    use \App\Http\Controllers\Concerns\ValidatesDormitoryOwnership;
    public function __construct(
        private readonly VisitWorkflowService $visit,
    ) {}

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $user = auth()->user();
        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }

        $query = DormitoryVisitLog::with(['student', 'room', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expected_arrival_datetime', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('visitor_name', 'like', "%{$q}%")
                ->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        $visits = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'pending')->count(),
            'approved' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'approved')->count(),
            'arrived' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'arrived')->count(),
            'checked_out' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'checked_out')->count(),
        ];

        return view('dormitory.visits.index', compact('dormitory', 'visits', 'userId', 'stats'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $user = auth()->user();
        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Find active year — prefer the one that has residents, otherwise any active year
        $activeYear = AcademicYear::where('is_active', true)->first();
        $yearId = $activeYear?->id;

        // Build query: filter by year only if residents with that year exist
        $residentsQuery = DormitoryResident::with('student.mahroms')
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true);

        if ($yearId) {
            $hasResidentsInYear = $residentsQuery->clone()->where('academic_year_id', $yearId)->exists();
            if ($hasResidentsInYear) {
                $residentsQuery->where('academic_year_id', $yearId);
            }
            // else: skip year filter — show all active residents in this dormitory
        }

        $residents = $residentsQuery->get();

        // flatten to students collection for the select dropdown
        $students = $residents->pluck('student')->filter()->values();

        return view('dormitory.visits.create', compact('dormitory', 'students', 'userId'));
    }

    /**
     * Cek kuota kunjungan keluarga per santri (JSON) — panel jatah & modal limit.
     */
    public function quotaCheck(Request $request, string $userId, string $asramaUuid)
    {
        $data = $request->validate([
            'student_id' => 'required|string',
        ]);

        $info = app(\App\Services\Boarding\VisitWorkflowService::class)
            ->inspectQuotaForVisit($data['student_id'], $asramaUuid);

        return response()->json($info);
    }

    public function store(StoreVisitRequest $request, string $userId, string $asramaUuid)
    {
        $data = $request->validated();

        $this->assertDormitoryResident($asramaUuid, $data['student_id']);
        if (! empty($data['room_id'])) {
            $this->assertDormitoryRoom($asramaUuid, $data['room_id']);
        }

        try {
            $visit = $this->visit->submit(
                data: $data,
                dormitoryId: $asramaUuid,
            );

            $autoApproved = false;
            if ($this->canAutoApproveRequest($request->user(), $asramaUuid)) {
                try {
                    $this->visit->approve($visit->id, $asramaUuid, 'Disetujui otomatis oleh Staf Perizinan/Staf Asrama.');
                    $autoApproved = true;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Auto-approve kunjungan gagal: '.$e->getMessage());
                }
            }

            return redirect()->route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
                ->with('success', $autoApproved
                    ? 'Permintaan kunjungan diajukan dan langsung disetujui.'
                    : 'Permintaan kunjungan berhasil diajukan.');
        } catch (QuotaExceededException $e) {
            \Log::warning('Kuota kunjungan habis', [
                'student_id' => $data['student_id'] ?? null,
                'dormitory' => $asramaUuid,
                'reason' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (\Throwable $th) {
            \Log::error($th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return back()->with('error', 'Terjadi kesalahan. Silangan coba lagi.');
        }
    }

    private function canAutoApproveRequest($user, string $dormitoryId): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'perizinanGenderScope') && $user->perizinanGenderScope() !== null) {
            return true;
        }

        if (DormitoryStaffAssignment::active()->forUser($user->id)->forDormitory($dormitoryId)->exists()) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'staf perizinan', 'staff perizinan', 'staf asrama', 'staff asrama',
            'musyrif', 'musyrifah', 'wali kamar',
        ]);
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $user = auth()->user();
        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }
        $visit = DormitoryVisitLog::with(['student', 'room', 'approvedBy', 'creator'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($visitUuid);

        return view('dormitory.visits.show', compact('dormitory', 'visit', 'userId'));
    }

    public function approve(ApproveVisitLogRequest $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->approve(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
            note: $request->input('approval_note'),
        );

        return back()->with('success', 'Kunjungan disetujui.');
    }

    public function reject(RejectVisitLogRequest $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->reject(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
            note: $request->input('reject_reason'),
        );

        return back()->with('success', 'Kunjungan ditolak.');
    }

    /**
     * AJAX endpoint: auto-match visitor to student's mahrom record.
     */
    public function findMahrom(string $asramaUuid, string $studentId): JsonResponse
    {
        $student = Student::where('id', $studentId)->firstOrFail();

        $mahroms = $student->mahroms()
            ->where('is_active', true)
            ->select('id', 'name', 'id_number', 'relationship', 'phone')
            ->get();

        return response()->json($mahroms);
    }

    public function checkIn(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->checkIn(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
            note: $request->input('note'),
        );

        return back()->with('success', 'Santri berhasil dicatat keluar asrama untuk kunjungan.');
    }

    public function checkOut(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->checkOut(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
            note: $request->input('note'),
        );

        return back()->with('success', 'Santri berhasil dicatat kembali ke asrama.');
    }

    public function scan(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $user = auth()->user();
        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }

        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $period = in_array($request->query('period'), ['today', 'week', 'month', 'all'], true)
            ? $request->query('period')
            : 'today';

        // Tanggal efektif untuk masing-masing sumber data (mengikuti period, kecuali user pilih date range eksplisit)
        [$fromDate, $toDate] = $this->resolveDateRange($period, $dateFrom, $dateTo);

        $baseQuery = function () use ($asramaUuid, $search) {
            $q = DormitoryVisitLog::with(['student', 'room'])
                ->where('dormitory_id', $asramaUuid);

            if ($search !== '') {
                $q->where(function ($sq) use ($search) {
                    $sq->where('visitor_name', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$search}%"));
                });
            }

            return $q;
        };

        // Santri belum keluar (kunjungan disetujui, menunggu santri dipanggil)
        $approvedAwaiting = $baseQuery()
            ->where('status', 'approved')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('expected_arrival_datetime', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderBy('expected_arrival_datetime')
            ->get();

        // Santri sedang di luar asrama menemui tamu (sudah scan keluar, belum kembali)
        $arrivedAwaiting = $baseQuery()
            ->where('status', 'arrived')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('check_in_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderByDesc('check_in_at')
            ->get();

        // Sudah keluar / sudah kembali ke asrama
        $returnedHome = $baseQuery()
            ->where('status', 'checked_out')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('check_out_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderByDesc('check_out_at')
            ->limit(20)
            ->get();

        $recentScans = $baseQuery()
            ->whereNotNull('check_in_at')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('check_in_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderByDesc('check_in_at')
            ->limit(10)
            ->get();

        $activeFilterCount = ($search !== '' ? 1 : 0) + ($period !== 'today' ? 1 : 0) + ($dateFrom ? 1 : 0) + ($dateTo ? 1 : 0);

        return view('dormitory.visits.scan', compact(
            'dormitory', 'userId', 'approvedAwaiting', 'arrivedAwaiting', 'returnedHome', 'recentScans',
            'search', 'dateFrom', 'dateTo', 'period', 'activeFilterCount'
        ));
    }

    /**
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
     * Process a QR scan. Accepts either:
     * - Full signed URL (from QR code)
     * - Visit UUID with valid signature parameters in payload
     */
    public function scanStore(Request $request, string $userId, string $asramaUuid)
    {
        $request->validate([
            'scan_url' => ['required', 'string', 'max:2048'],
        ]);

        $scanUrl = trim((string) $request->input('scan_url'));

        // QR kartu kunjungan berisi token bertanda tangan (id::hmac).
        $visit = $this->resolveVisitQrToken($scanUrl, $asramaUuid);

        // Kompatibilitas dengan QR lama berupa signed URL.
        if (! $visit && preg_match('/^https?:\/\//i', $scanUrl)) {
            $signedRequest = Request::create($scanUrl, 'GET');

            if ($signedRequest->hasValidSignature()) {
                $visitUuid = $signedRequest->query('visitUuid');
                $visit = $visitUuid
                    ? DormitoryVisitLog::where('dormitory_id', $asramaUuid)->find($visitUuid)
                    : null;
            }
        }

        if (! $visit) {
            return $this->scanResponse($request, 'QR kunjungan tidak valid atau sudah kedaluwarsa.', false);
        }

        // 3. Auto check-in / check-out based on status
        if ($visit->status === 'approved') {
            $this->visit->checkIn(visitId: $visit->id, dormitoryId: $asramaUuid);

            return $this->scanResponse(
                $request,
                "Santri berhasil dicatat keluar asrama menemui {$visit->visitor_name}. Status: Santri Sedang di Luar.",
                true,
                ['visitor_name' => $visit->visitor_name, 'status' => 'arrived']
            );
        }

        if ($visit->status === 'arrived') {
            $this->visit->checkOut(visitId: $visit->id, dormitoryId: $asramaUuid);

            return $this->scanResponse(
                $request,
                "Santri berhasil dicatat kembali ke asrama. Status: Santri Sudah Kembali.",
                true,
                ['visitor_name' => $visit->visitor_name, 'status' => 'checked_out']
            );
        }

        if ($visit->status === 'checked_out') {
            $waktu = $visit->check_out_at?->format('d/m/Y H:i') ?? '-';

            return $this->scanResponse(
                $request,
                "KARTU KADALUARSA — kunjungan untuk santri ".($visit->student?->name ?? '-')." telah selesai pada {$waktu}. QR Code kartu kunjungan ini tidak berlaku lagi.",
                false,
                ['visitor_name' => $visit->visitor_name, 'status' => 'expired', 'checked_out_at' => $waktu]
            );
        }

        return $this->scanResponse($request, "Kunjungan berstatus '{$visit->status}' — tidak bisa di-scan saat ini.", false);
    }

    /**
     * Validasi token QR kartu kunjungan: base64(id::hmac).
     */
    private function resolveVisitQrToken(string $raw, string $asramaUuid): ?DormitoryVisitLog
    {
        $decoded = base64_decode(trim($raw), true);

        if (! $decoded || ! str_contains($decoded, '::')) {
            return null;
        }

        [$visitId, $providedSignature] = explode('::', $decoded, 2);
        $expectedSignature = hash_hmac('sha256', $visitId, (string) config('app.key'));

        if (! hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        return DormitoryVisitLog::where('dormitory_id', $asramaUuid)->find($visitId);
    }

    /**
     * Return JSON for AJAX scans, redirect for HTML form fallback.
     */
    private function scanResponse(Request $request, string $message, bool $success, array $data = [])
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $data));
        }

        return back()->with($success ? 'success' : 'error', $message)->withInput();
    }

    /**
     * Public QR verification — shows visit details if signature valid.
     */
    /**
     * Show printable visit card.
     */
    public function card(string $userId, string $asramaUuid, string $visitUuid)
    {
        // Cetak kartu kunjungan langsung PDF (58 mm), bukan preview HTML.
        return $this->cardPdf($userId, $asramaUuid, $visitUuid);
    }

    /**
     * PDF version of a single visit card.
     */
    public function cardPdf(string $userId, string $asramaUuid, string $visitUuid)
    {
        try {
            $dormitory = Dormitory::findOrFail($asramaUuid);
            $user = auth()->user();
            if ($user && ! $user->canAccessDormitory($asramaUuid)) {
                abort(403, 'Anda tidak memiliki akses ke asrama ini.');
            }
            $visit = DormitoryVisitLog::with(['student', 'room', 'approvedBy'])
                ->where('dormitory_id', $asramaUuid)
                ->findOrFail($visitUuid);

            $now = now();

            $html = view('dormitory.visits.card-pdf', compact('dormitory', 'visit', 'userId', 'now'))->render();

            $options = (new Options)
                ->set('isRemoteEnabled', false)
                ->set('isHtml5ParserEnabled', true)
                ->set('defaultFont', 'Courier');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, 175, 600]); // ~58mm width
            $dompdf->render();

            $output = $dompdf->output();
            $filename = 'kartu-kunjungan-'.($visit->visitor_name ?? $visit->id).'-'.now()->format('Ymd-His').'.pdf';

            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($output),
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating visit card PDF: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());

            return redirect()->back()->with('error', 'Gagal membuat PDF: '.$e->getMessage());
        }
    }

    public function verify(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Tanda tangan QR tidak valid atau sudah kadaluarsa.');
        }

        $visitUuid = $request->query('visitUuid');
        $visit = $visitUuid
            ? DormitoryVisitLog::with(['student', 'dormitory'])->find($visitUuid)
            : null;

        return view('dormitory.visits.verify', compact('visit'));
    }
}
