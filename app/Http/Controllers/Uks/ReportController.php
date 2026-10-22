<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\DormitoryPermit;
use App\Models\DormitoryReward;
use App\Models\DormitoryViolation;
use App\Models\SanitationInspection;
use App\Models\Student;
use App\Models\StudentHealthMetric;
use App\Models\StudentMedicineInventory;
use App\Models\Uks\UksPatient;
use App\Models\UksBed;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $patientBase = UksPatient::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $stats = [
            'patients' => (clone $patientBase)->count(),
            'active' => (clone $patientBase)->active()->count(),
            'inpatient' => (clone $patientBase)->where('status', UksPatient::STATUS_INPATIENT)->count(),
            'beds_available' => UksBed::query()->available()->count(),
            'sanitation' => SanitationInspection::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->count(),
            'permits' => DormitoryPermit::query()->count(),
        ];

        return view('uks.reports.index', compact('stats'));
    }

    public function attendance(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $status = $request->input('status');

        $patients = UksPatient::with('student')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereYear('arrived_at', $year)
            ->whereMonth('arrived_at', $month)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('arrived_at')
            ->paginate(25)
            ->withQueryString();

        $monthlyTotals = UksPatient::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereYear('arrived_at', $year)
            ->whereMonth('arrived_at', $month)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('uks.reports.attendance', compact('patients', 'month', 'year', 'status', 'monthlyTotals'));
    }

    public function occupancy(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $careLocation = $request->input('care_location');

        $patients = UksPatient::with(['student', 'currentBedAssignment.bed'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->active()
            ->when($careLocation, fn ($q) => $q->where('care_location', $careLocation))
            ->orderByDesc('admitted_at')
            ->paginate(25)
            ->withQueryString();

        return view('uks.reports.occupancy', compact('patients', 'careLocation'));
    }

    public function permits(Request $request)
    {
        $status = $request->input('status');
        $month = $request->input('month');
        $year = $request->input('year');

        $permits = DormitoryPermit::with(['student'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($month, fn ($q) => $q->whereMonth('created_at', $month))
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('uks.reports.permits', compact('permits', 'status', 'month', 'year'));
    }

    public function studentDetail(Request $request, string $userId, string $studentId)
    {
        $student = Student::with('school')->findOrFail($studentId);

        $patients = UksPatient::with(['currentBedAssignment.bed'])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $metrics = StudentHealthMetric::where('student_id', $studentId)
            ->orderByDesc('record_date')
            ->limit(25)
            ->get();

        return view('uks.reports.student-detail', compact('student', 'patients', 'metrics', 'userId'));
    }

    public function violations(Request $request)
    {
        $violations = DormitoryViolation::with(['student'])
            ->orderByDesc('violation_date')
            ->paginate(25)
            ->withQueryString();

        return view('uks.reports.violations', compact('violations'));
    }

    public function rewards(Request $request)
    {
        $rewards = DormitoryReward::with(['student'])
            ->orderByDesc('awarded_date')
            ->paginate(25)
            ->withQueryString();

        return view('uks.reports.rewards', compact('rewards'));
    }

    public function inventories(Request $request)
    {
        $inventories = StudentMedicineInventory::query()
            ->orderBy('medicine_name')
            ->paginate(25)
            ->withQueryString();

        return view('uks.reports.inventories', compact('inventories'));
    }

    public function exportCsv(Request $request, string $userId, string $report)
    {
        [$title, $headers, $rows] = $this->reportRows($request, $report);
        $filename = Str::slug($title).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request, string $userId, string $report)
    {
        [$title, $headers, $rows] = $this->reportRows($request, $report);

        $pdf = new Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $pdf->loadHtml(view('uks.reports.pdf', compact('title', 'headers', 'rows'))->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($title).'-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    private function reportRows(Request $request, string $report): array
    {
        $schoolId = $request->attributes->get('schoolContextId');

        return match ($report) {
            'attendance' => ['Laporan Presensi UKS', ['Tanggal', 'Santri', 'Status', 'Lokasi'], UksPatient::with('student')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($request->filled('month'), fn ($q) => $q->whereMonth('arrived_at', $request->month))
                ->when($request->filled('year'), fn ($q) => $q->whereYear('arrived_at', $request->year))
                ->orderBy('arrived_at')->get()->map(fn ($p) => [
                    $p->arrived_at?->format('d/m/Y H:i') ?? '—',
                    $p->student?->name ?? '—',
                    UksPatient::$statusLabels[$p->status] ?? $p->status,
                    $p->care_location_label ?? '—',
                ])->all()],
            'occupancy' => ['Laporan Penghuni UKS', ['Santri', 'Status', 'Lokasi', 'Bed'], UksPatient::with(['student', 'currentBedAssignment.bed'])
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->active()
                ->orderByDesc('admitted_at')->get()->map(fn ($p) => [
                    $p->student?->name ?? '—',
                    UksPatient::$statusLabels[$p->status] ?? $p->status,
                    $p->care_location_label ?? '—',
                    $p->currentBedAssignment?->bed?->identifier ?? $p->bed_number ?? '—',
                ])->all()],
            'permits' => ['Laporan Perizinan', ['Santri', 'Jenis', 'Tujuan', 'Berangkat', 'Status'], DormitoryPermit::with('student')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('month'), fn ($q) => $q->whereMonth('created_at', $request->month))
                ->when($request->filled('year'), fn ($q) => $q->whereYear('created_at', $request->year))
                ->orderByDesc('created_at')->get()->map(fn ($p) => [
                    $p->student?->name ?? '—',
                    $p->permit_type ?? '—',
                    $p->destination ?? '—',
                    $p->departure_datetime?->format('d/m/Y H:i') ?? '—',
                    $p->status ?? '—',
                ])->all()],
            'sanitation' => ['Laporan Kebersihan', ['Tanggal', 'Lokasi', 'Skor', 'Status', 'Petugas'], SanitationInspection::with('inspectedBy')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($request->filled('month'), fn ($q) => $q->whereMonth('inspection_date', $request->month))
                ->when($request->filled('year'), fn ($q) => $q->whereYear('inspection_date', $request->year))
                ->orderByDesc('inspection_date')->get()->map(fn ($i) => [
                    $i->inspection_date?->format('d/m/Y') ?? '—',
                    $i->location_type_text ?? $i->location_type ?? '—',
                    $i->score ?? 0,
                    $i->is_passed ? 'Lulus' : 'Perlu Tindak Lanjut',
                    $i->inspectedBy?->name ?? '—',
                ])->all()],
            'violations' => ['Laporan Pelanggaran', ['Tanggal', 'Santri', 'Kategori', 'Poin', 'Tindakan'], DormitoryViolation::with('student')
                ->orderByDesc('violation_date')->get()->map(fn ($v) => [
                    $v->violation_date?->format('d/m/Y') ?? '—',
                    $v->student?->name ?? '—',
                    $v->violation_category ?? '—',
                    $v->points ?? 0,
                    $v->action_taken ?? '—',
                ])->all()],
            'rewards' => ['Laporan Penghargaan', ['Tanggal', 'Santri', 'Penghargaan', 'Kategori', 'Level'], DormitoryReward::with('student')
                ->orderByDesc('awarded_date')->get()->map(fn ($r) => [
                    $r->awarded_date?->format('d/m/Y') ?? '—',
                    $r->student?->name ?? '—',
                    $r->title ?? '—',
                    $r->category ?? '—',
                    $r->level ?? '—',
                ])->all()],
            'inventories' => ['Laporan Inventaris UKS', ['Nama', 'Kategori', 'Stok', 'Satuan', 'Kedaluwarsa'], StudentMedicineInventory::orderBy('medicine_name')->get()->map(fn ($i) => [
                $i->medicine_name,
                $i->category ?? '—',
                $i->current_stock ?? 0,
                $i->unit ?? '—',
                $i->expiry_date?->format('d/m/Y') ?? '—',
            ])->all()],
            default => abort(404),
        };
    }

    public function sanitation(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $month = $request->input('month');
        $year = $request->input('year');
        $passed = $request->input('passed');

        $inspections = SanitationInspection::with(['inspectedBy'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($month, fn ($q) => $q->whereMonth('inspection_date', $month))
            ->when($year, fn ($q) => $q->whereYear('inspection_date', $year))
            ->when($passed !== null && $passed !== '', fn ($q) => $q->where('is_passed', (bool) $passed))
            ->orderByDesc('inspection_date')
            ->paginate(25)
            ->withQueryString();

        return view('uks.reports.sanitation', compact('inspections', 'month', 'year', 'passed'));
    }
}
