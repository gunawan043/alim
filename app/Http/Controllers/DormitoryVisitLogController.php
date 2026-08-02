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
use App\Models\Student;
use App\Services\Boarding\VisitWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DormitoryVisitLogController extends Controller
{
    public function __construct(
        private readonly VisitWorkflowService $visit,
    ) {}

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

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

    public function store(StoreVisitRequest $request, string $userId, string $asramaUuid)
    {
        $data = $request->validated();

        try {
            $this->visit->submit(
                data: $data,
                dormitoryId: $asramaUuid,
            );

            return redirect()->route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
                ->with('success', 'Permintaan kunjungan berhasil diajukan.');
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

    public function show(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
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

        return back()->with('success', 'Check-in kunjungan berhasil.');
    }

    public function checkOut(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->checkOut(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
            note: $request->input('note'),
        );

        return back()->with('success', 'Check-out kunjungan berhasil.');
    }

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

        // Santri yang belum keluar (menunggu penjenguk datang)
        $approvedAwaiting = $baseQuery()
            ->where('status', 'approved')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('expected_arrival_datetime', [$fromDate.' 00:00:00', $toDate.' 23:59:59']))
            ->orderBy('expected_arrival_datetime')
            ->get();

        // Belum kembali dari penjengukan (sudah check-in tamu, anak belum pulang)
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

        $scanUrl = $request->input('scan_url');

        // 1. Reject if signature invalid
        if (! $request->hasValidSignature()) {
            return $this->scanResponse($request, 'Tanda tangan QR tidak valid atau sudah kadaluarsa.', false);
        }

        // 2. Find the visit by the signed URL's visitUuid parameter
        $visitUuid = $request->query('visitUuid');
        if (! $visitUuid) {
            return $this->scanResponse($request, 'QR tidak berisi ID kunjungan.', false);
        }

        $visit = DormitoryVisitLog::where('dormitory_id', $asramaUuid)->find($visitUuid);
        if (! $visit) {
            return $this->scanResponse($request, 'Kunjungan tidak ditemukan untuk asrama ini.', false);
        }

        // 3. Auto check-in / check-out based on status
        if ($visit->status === 'approved') {
            $this->visit->checkIn(visitId: $visit->id, dormitoryId: $asramaUuid);

            return $this->scanResponse(
                $request,
                "Check-in {$visit->visitor_name} berhasil via scan.",
                true,
                ['visitor_name' => $visit->visitor_name, 'status' => 'arrived']
            );
        }

        if ($visit->status === 'arrived') {
            $this->visit->checkOut(visitId: $visit->id, dormitoryId: $asramaUuid);

            return $this->scanResponse(
                $request,
                "Check-out {$visit->visitor_name} berhasil via scan.",
                true,
                ['visitor_name' => $visit->visitor_name, 'status' => 'checked_out']
            );
        }

        return $this->scanResponse($request, "Kunjungan berstatus '{$visit->status}' — tidak bisa di-scan saat ini.", false);
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
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $visit = DormitoryVisitLog::with(['student', 'room', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($visitUuid);

        return view('dormitory.visits.card', compact('dormitory', 'visit', 'userId'));
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
