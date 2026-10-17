<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\AuditLog;
use App\Models\DormitoryActivity;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryInventory;
use App\Exports\DormitoryPermitsExport;
use App\Models\DormitoryPermit;
use App\Models\DormitoryEmergencyBroadcast;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoomMove;
use App\Models\DormitoryReward;
use App\Models\DormitoryRoom;
use App\Models\DormitoryViolation;
use App\Models\DormitoryVisitLog;
use App\Models\SanitationInspection;
use App\Models\Student;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DormitoryReportController extends Controller
{
    private function enforceScope(string $asramaUuid): Dormitory
    {
        $user = request()->user();
        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }

        return Dormitory::findOrFail($asramaUuid);
    }

    // ── Dashboard ──────────────────────────────────────────────────

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->enforceScope($asramaUuid);
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

        $dormitory = $this->enforceScope($asramaUuid);
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

        $dormitory = $this->enforceScope($asramaUuid);
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

    /**
     * Laporan perizinan — Excel rapi & terstruktur (bulk per periode).
     */
    public function permitsExcel(Request $request, string $userId, string $asramaUuid)
    {
        $this->validate($request, [
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020',
            'permit_type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'student_id' => 'nullable|string|max:50',
        ]);

        $dormitory = $this->enforceScope($asramaUuid);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $records = DormitoryPermit::with(['student:id,name,nisn', 'room:id,code,name'])
            ->where('dormitory_id', $asramaUuid)
            ->when($request->filled('permit_type'), fn ($q) => $q->where('permit_type', $request->permit_type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at')
            ->get();

        return Excel::download(
            new DormitoryPermitsExport($records, $dormitory, $month, $year),
            'laporan_perizinan_'.$dormitory->code.'_'.$month.'_'.$year.'.xlsx'
        );
    }

    // ── Laporan Pelanggaran (CSV) ─────────────────────────────────

    public function violations(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $this->validate($request, [
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020',
        ]);

        $dormitory = $this->enforceScope($asramaUuid);
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

        $dormitory = $this->enforceScope($asramaUuid);
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
        $dormitory = $this->enforceScope($asramaUuid);

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
        $dormitory = $this->enforceScope($asramaUuid);

        $roomIds = DormitoryRoom::where('dormitory_id', $asramaUuid)->pluck('id');
        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)->pluck('name', 'id');

        $base = SanitationInspection::query()
            ->where('location_type', 'asrama')
            ->where(function ($q) use ($roomIds, $asramaUuid) {
                $q->whereIn('location_id', $roomIds)->orWhere('location_id', $asramaUuid);
            });

        $summary = [
            'total' => (clone $base)->count(),
            'avg_score' => round((float) ((clone $base)->avg('score') ?? 0), 1),
            'passed' => (clone $base)->where('is_passed', true)->count(),
            'pending_follow_up' => (clone $base)->pendingFollowUp()->count(),
        ];

        $recentInspections = (clone $base)->with(['inspectedBy'])
            ->orderByDesc('inspection_date')
            ->limit(50)
            ->get();

        return view('dormitory.reports.sanitation', compact(
            'dormitory', 'summary', 'rooms', 'recentInspections', 'userId', 'asramaUuid',
        ));
    }

    // ── Rekap Santri (per Kamar) ─────────────────────────────────────

    public function occupancy(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->enforceScope($asramaUuid);
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

    // ── Audit Log Asrama (HTML) ───────────────────────────────────

    public function auditLog(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->enforceScope($asramaUuid);

        $permitIds = DormitoryPermit::where('dormitory_id', $asramaUuid)->pluck('id');
        $moveIds = DormitoryRoomMove::where('dormitory_id', $asramaUuid)->pluck('id');
        $activityIds = DormitoryActivity::where('dormitory_id', $asramaUuid)->pluck('id');
        $broadcastIds = DormitoryEmergencyBroadcast::where('dormitory_id', $asramaUuid)->pluck('id');

        $query = AuditLog::with('user')
            ->where(function ($q) use ($permitIds, $moveIds, $activityIds, $broadcastIds) {
                $q->where(fn ($x) => $x->where('table_name', 'dormitory_permits')->whereIn('record_id', $permitIds))
                    ->orWhere(fn ($x) => $x->where('table_name', 'dormitory_room_moves')->whereIn('record_id', $moveIds))
                    ->orWhere(fn ($x) => $x->where('table_name', 'dormitory_activities')->whereIn('record_id', $activityIds))
                    ->orWhere(fn ($x) => $x->where('table_name', 'dormitory_emergency_broadcasts')->whereIn('record_id', $broadcastIds));
            })
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->orderByDesc('created_at');

        $logs = $query->paginate(30)->withQueryString();

        return view('dormitory.reports.audit', compact('dormitory', 'logs', 'userId', 'asramaUuid'));
    }

    // ── Laporan Kunjungan (CSV) ──────────────────────────────────

    public function visits(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $dormitory = $this->enforceScope($asramaUuid);
        $period = $this->getPeriod($request);
        $month = $period['month'];
        $year = $period['year'];

        $records = DormitoryVisitLog::with(['student', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->whereYear('expected_arrival_datetime', $year)
            ->whereMonth('expected_arrival_datetime', $month)
            ->orderByDesc('expected_arrival_datetime')
            ->get();

        return $this->streamCsv('laporan_kunjungan_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'Santri', 'Kamar', 'Pengunjung', 'Hubungan', 'Tujuan', 'Status', 'Check-in', 'Check-out']);
            foreach ($records as $row) {
                fputcsv($out, [
                    $row->expected_arrival_datetime?->format('Y-m-d H:i'),
                    $row->student?->name,
                    $row->room?->name,
                    $row->visitor_name,
                    $row->visitor_relationship,
                    $row->purpose,
                    $row->status,
                    $row->check_in_at?->format('d/m/Y H:i'),
                    $row->check_out_at?->format('d/m/Y H:i'),
                ]);
            }
            fclose($out);
        });
    }

    // ── Laporan Mutasi Kamar (CSV) ───────────────────────────────

    public function roomMoves(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $dormitory = $this->enforceScope($asramaUuid);
        $period = $this->getPeriod($request);
        $month = $period['month'];
        $year = $period['year'];

        $records = DormitoryRoomMove::with(['student', 'fromRoom', 'toRoom', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->whereYear('move_date', $year)
            ->whereMonth('move_date', $month)
            ->orderByDesc('move_date')
            ->get();

        return $this->streamCsv('laporan_mutasi_kamar_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'Santri', 'Dari Kamar', 'Ke Kamar', 'Jenis', 'Alasan', 'Status', 'Disetujui Oleh']);
            foreach ($records as $row) {
                fputcsv($out, [
                    $row->move_date?->format('Y-m-d'),
                    $row->student?->name,
                    $row->fromRoom?->name,
                    $row->toRoom?->name,
                    $row->move_type,
                    $row->reason,
                    $row->approval_status,
                    $row->approvedBy?->name,
                ]);
            }
            fclose($out);
        });
    }

    // ── Laporan Keluar Asrama / Check-out (CSV) ───────────────────

    public function checkouts(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $dormitory = $this->enforceScope($asramaUuid);
        $period = $this->getPeriod($request);
        $month = $period['month'];
        $year = $period['year'];

        $records = DormitoryResident::with(['student', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', false)
            ->whereNotNull('check_out_date')
            ->whereYear('check_out_date', $year)
            ->whereMonth('check_out_date', $month)
            ->orderByDesc('check_out_date')
            ->get();

        return $this->streamCsv('laporan_keluar_asrama_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal Keluar', 'Santri', 'Kamar', 'Alasan', 'Catatan']);
            foreach ($records as $row) {
                fputcsv($out, [
                    $row->check_out_date?->format('Y-m-d'),
                    $row->student?->name,
                    $row->room?->name,
                    $row->check_out_reason,
                    $row->notes,
                ]);
            }
            fclose($out);
        });
    }

    // ── Laporan Kegiatan Asrama (CSV) ─────────────────────────────

    public function activities(Request $request, string $userId, string $asramaUuid): StreamedResponse
    {
        $dormitory = $this->enforceScope($asramaUuid);
        $period = $this->getPeriod($request);
        $month = $period['month'];
        $year = $period['year'];

        $records = DormitoryActivity::query()
            ->where('dormitory_id', $asramaUuid)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->withCount([
                'attendances as hadir_count' => fn ($q) => $q->where('status', 'hadir'),
                'attendances as izin_count' => fn ($q) => $q->where('status', 'izin'),
                'attendances as sakit_count' => fn ($q) => $q->where('status', 'sakit'),
                'attendances as alpa_count' => fn ($q) => $q->where('status', 'alpa'),
                'attendances as total_count',
            ])
            ->orderByDesc('activity_date')
            ->get();

        return $this->streamCsv('laporan_kegiatan_'.$dormitory->code.'_'.$month.'_'.$year.'.csv', function () use ($records) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'Kegiatan', 'Waktu', 'Lokasi', 'Status', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Total']);
            foreach ($records as $row) {
                fputcsv($out, [
                    $row->activity_date?->format('Y-m-d'),
                    $row->name,
                    trim(($row->start_time ? substr($row->start_time, 0, 5) : '').' - '.($row->end_time ? substr($row->end_time, 0, 5) : ''), ' -'),
                    $row->location,
                    $row->status,
                    $row->hadir_count,
                    $row->izin_count,
                    $row->sakit_count,
                    $row->alpa_count,
                    $row->total_count,
                ]);
            }
            fclose($out);
        });
    }

    public function studentDetail(string $userId, string $asramaUuid, string $studentId)
    {
        $dormitory = $this->enforceScope($asramaUuid);
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
