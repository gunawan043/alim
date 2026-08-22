<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryInventory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryReward;
use App\Models\DormitoryRoom;
use App\Models\DormitoryViolation;
use App\Models\SanitationInspection;
use App\Models\Student;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DormitoryReportController extends Controller
{
    // ── Dashboard ──────────────────────────────────────────────────

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $period = $this->getPeriod($request);

        return view('dormitory.reports.index', compact(
            'dormitory',
            'period',
            'activeYear',
            'userId',
            'asramaUuid',
        ));
    }

    // ── Laporan Presensi (CSV) ───────────────────────────────────

    public function attendance(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $this->validate($request, [
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020',
        ]);

        $dormitory = Dormitory::findOrFail($asramaUuid);
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $records = DormitoryAttendance::with('student:id,name,nisn')
            ->where('dormitory_id', $asramaUuid)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->orderBy('student_id')
            ->get();

        return $this->streamCsv('laporan_presensi_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['Tanggal', 'Sesi', 'NISN', 'Nama Santri', 'Status', 'Keterangan']);

            foreach ($records as $r) {
                fputcsv($fp, [
                    $r->attendance_date->format('Y-m-d'),
                    $r->session ?? '-',
                    $r->student->nisn ?? '-',
                    $r->student->name ?? '-',
                    $r->status ?? '-',
                    $r->notes ?? '-',
                ]);
            }

            fclose($fp);
        });
    }

    // ── Laporan Perizinan (CSV) ───────────────────────────────────

    public function permits(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $this->validate($request, [
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020',
        ]);

        $dormitory = Dormitory::findOrFail($asramaUuid);
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $records = DormitoryPermit::with('student:id,name,nisn')
            ->where('dormitory_id', $asramaUuid)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at')
            ->get();

        return $this->streamCsv('laporan_perizinan_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, [
                'Tanggal Pengajuan',
                'NISN',
                'Nama Santri',
                'Jenis Izin',
                'Kategori',
                'Tujuan',
                'Perihal',
                'Berangkat',
                'Estimasi Kembali',
                'Penjemput',
                'Mode Penjemputan',
                'Diverifikasi QR',
                'Waktu QR Discan',
                'Kepulangan Aktual',
                'Pemulangan Oleh',
                'Status',
                'Emergency Contact',
            ]);

            foreach ($records as $p) {
                fputcsv($fp, [
                    $p->created_at?->format('Y-m-d'),
                    $p->student->nisn ?? '-',
                    $p->student->name ?? '-',
                    $p->permit_type ?? '-',
                    $p->category ?? '-',
                    $p->destination ?? '-',
                    $p->purpose ?? '-',
                    $p->departure_datetime?->format('Y-m-d H:i'),
                    $p->expected_return_datetime?->format('Y-m-d H:i'),
                    $p->companion_name ?? '-',
                    $p->pickup_mode ?? '-',
                    $p->scan_token ? ($p->scanned_at ? 'Ya' : 'Belum') : '—',
                    $p->scanned_at?->format('Y-m-d H:i'),
                    $p->actual_return_datetime?->format('Y-m-d H:i'),
                    $p->return_by ?? '-',
                    $p->status ?? '-',
                    $p->emergency_contact_text ?? '-',
                ]);
            }

            fclose($fp);
        });
    }

    // ── Laporan Pelanggaran (CSV) ─────────────────────────────────

    public function violations(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $this->validate($request, [
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020',
        ]);

        $dormitory = Dormitory::findOrFail($asramaUuid);
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $records = DormitoryViolation::with('student:id,name,nisn')
            ->where('dormitory_id', $asramaUuid)
            ->whereYear('violation_date', $year)
            ->whereMonth('violation_date', $month)
            ->orderBy('violation_date')
            ->get();

        return $this->streamCsv('laporan_pelanggaran_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, [
                'Tanggal',
                'NISN',
                'Nama Santri',
                'Kategori',
                'Jenis',
                'Poin',
                'Deskripsi',
                'Tindakan',
            ]);

            foreach ($records as $v) {
                fputcsv($fp, [
                    $v->violation_date?->format('Y-m-d'),
                    $v->student->nisn ?? '-',
                    $v->student->name ?? '-',
                    $v->violation_category ?? '-',
                    $v->violation_type ?? '-',
                    $v->points ?? 0,
                    $v->description ?? '-',
                    $v->action_taken ?? '-',
                ]);
            }

            fclose($fp);
        });
    }

    // ── Laporan Penghargaan (CSV) ─────────────────────────────────

    public function rewards(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $this->validate($request, [
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020',
        ]);

        $dormitory = Dormitory::findOrFail($asramaUuid);
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $records = DormitoryReward::with('student:id,name,nisn')
            ->where('dormitory_id', $asramaUuid)
            ->whereYear('awarded_date', $year)
            ->whereMonth('awarded_date', $month)
            ->orderBy('awarded_date')
            ->get();

        return $this->streamCsv('laporan_penghargaan_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, [
                'Tanggal',
                'NISN',
                'Nama Santri',
                'Judul',
                'Kategori',
                'Level',
                'Pemberi',
            ]);

            foreach ($records as $r) {
                fputcsv($fp, [
                    $r->awarded_date?->format('Y-m-d'),
                    $r->student->nisn ?? '-',
                    $r->student->name ?? '-',
                    $r->title ?? '-',
                    $r->category_text ?? '-',
                    $r->level_text ?? '-',
                    $r->givenBy->name ?? '-',
                ]);
            }

            fclose($fp);
        });
    }

    // ── Laporan Inventaris (HTML Summary) ─────────────────────────

    public function inventoriesHtml(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $byCondition = DormitoryInventory::where('dormitory_id', $asramaUuid)
            ->selectRaw('condition, COUNT(*) as count')
            ->groupBy('condition')
            ->pluck('count', 'condition');

        $totalItems = DormitoryInventory::where('dormitory_id', $asramaUuid)->sum('quantity');
        $goodItems = DormitoryInventory::where('dormitory_id', $asramaUuid)
            ->where('condition', 'baik')->sum('quantity');
        $damagedItems = DormitoryInventory::where('dormitory_id', $asramaUuid)
            ->where('condition', 'rusak')->sum('quantity');

        return view('dormitory.reports.inventory', compact(
            'dormitory',
            'byCondition',
            'totalItems',
            'goodItems',
            'damagedItems',
            'userId',
            'asramaUuid',
        ));
    }

    // ── Laporan Kebersihan (HTML Summary) ─────────────────────────

    public function sanitationHtml(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $byLocation = SanitationInspection::selectRaw('location_type, AVG(score) as avg_score, COUNT(*) as total')
            ->where('location_type', 'asrama')
            ->groupBy('location_type')
            ->get();

        $recentInspections = SanitationInspection::with(['inspectedBy'])
            ->where(function ($q) {
                $q->where('location_type', 'asrama');
            })
            ->orderByDesc('inspection_date')
            ->limit(50)
            ->get();

        return view('dormitory.reports.sanitation', compact(
            'dormitory',
            'byLocation',
            'recentInspections',
            'userId',
            'asramaUuid',
        ));
    }

    // ── Rekap Santri (per Kamar) ─────────────────────────────────────

    public function occupancy(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $rooms = DormitoryRoom::withCount([
            'residents as occupied_count' => fn ($q) => $q
                ->where('academic_year_id', $activeYear?->id)
                ->where('is_active', true),
        ])
            ->where('dormitory_id', $asramaUuid)
            ->orderBy('code')
            ->get();

        $totalOccupied = $rooms->sum('occupied_count');
        $totalCapacity = $rooms->sum('capacity');

        return view('dormitory.reports.occupancy', compact(
            'dormitory',
            'rooms',
            'totalOccupied',
            'totalCapacity',
            'userId',
            'asramaUuid',
        ));
    }

    // ── Student Profile Tab ───────────────────���───────────────────

    public function studentDetail(string $userId, string $asramaUuid, string $studentId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $student = Student::findOrFail($studentId);

        $residents = DormitoryResident::with(['room.wing'])
            ->where('student_id', $studentId)
            ->where('dormitory_id', $asramaUuid)
            ->orderByDesc('check_in_date')
            ->get();

        $recentPermits = DormitoryPermit::where('student_id', $studentId)
            ->where('dormitory_id', $asramaUuid)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentViolations = DormitoryViolation::where('student_id', $studentId)
            ->where('dormitory_id', $asramaUuid)
            ->orderByDesc('violation_date')
            ->limit(10)
            ->get();

        $recentRewards = DormitoryReward::where('student_id', $studentId)
            ->where('dormitory_id', $asramaUuid)
            ->orderByDesc('awarded_date')
            ->limit(10)
            ->get();

        return view('dormitory.reports.student_detail', compact(
            'dormitory',
            'student',
            'residents',
            'recentPermits',
            'recentViolations',
            'recentRewards',
            'userId',
        ));
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function getPeriod(Request $request): array
    {
        return [
            'month' => (int) $request->input('month', now()->month),
            'year' => (int) $request->input('year', now()->year),
        ];
    }

    private function streamCsv(string $filename, callable $callback): StreamedResponse
    {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
