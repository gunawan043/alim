<?php

namespace App\Http\Controllers;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Events\TeacherCheckedOut;
use App\Events\TeacherQrScanned;
use App\Models\AbsensiGtkSetting;
use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\QrClassToken;
use App\Models\TeacherClassAttendance;
use App\Models\User;
use App\Services\QrTokenService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TeacherQrScanController extends Controller
{
    protected QrTokenService $qrTokenService;

    public function __construct(QrTokenService $qrTokenService)
    {
        $this->qrTokenService = $qrTokenService;
    }

    /**
     * Scan page — shows camera/manual input for both check-in and check-out.
     */
    public function scanIndex(Request $request)
    {
        $user = $request->user();
        $academicYear = AcademicYear::where('is_active', true)->first();

        $today = today();
        $dayOfWeek = (int) $today->format('w') === 0 ? 7 : (int) $today->format('w');

        $schedules = JadwalKbm::where('teacher_id', $user->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereHas('academicYear', fn ($q) => $q->where('id', $academicYear?->id))
            ->with(['studyGroup' => fn ($q) => $q->with('gradeLevel')])
            ->orderBy('slot_index')
            ->get();

        $attendances = TeacherClassAttendance::where('teacher_id', $user->id)
            ->where('attendance_date', $today)
            ->with(['jadwalKbm', 'studyGroup'])
            ->get()
            ->keyBy(fn ($a) => $a->jadwal_kbm_id);

        $needsCheckout = $schedules->filter(function ($jadwal) use ($attendances) {
            $att = $attendances->get($jadwal->id);

            return $att && $att->actual_time_in && ! $att->actual_time_out;
        });

        $stats = [
            'total' => $schedules->count(),
            'checked_in' => $schedules->filter(fn ($s) => isset($attendances[$s->id]) && $attendances[$s->id]->actual_time_in)->count(),
            'checked_out' => $schedules->filter(fn ($s) => isset($attendances[$s->id]) && $attendances[$s->id]->actual_time_out)->count(),
            'late' => $schedules->filter(fn ($s) => isset($attendances[$s->id]) && $attendances[$s->id]->status_masuk === 'terlambat')->count(),
            'pending' => $schedules->count() - $schedules->filter(fn ($s) => isset($attendances[$s->id]))->count(),
        ];

        $recentRecords = TeacherClassAttendance::where('teacher_id', $user->id)
            ->where('attendance_date', '>=', $today->subDays(6))
            ->with(['jadwalKbm.studyGroup', 'jadwalKbm.subject'])
            ->orderByDesc('attendance_date')
            ->limit(5)
            ->get();

        return view('teacher.qr.scan.index', compact(
            'schedules',
            'attendances',
            'needsCheckout',
            'academicYear',
            'stats',
            'recentRecords'
        ));
    }

    /**
     * Process QR scan (check-in or check-out).
     * Accepts signed URL from QR code.
     */
    public function scanProcess(Request $request, string $studyGroupId)
    {
        // Verify signature (shared with the QR URL)
        if (! $request->hasValidSignature()) {
            return $this->scanFailure($request, 'Tanda tangan QR tidak valid atau sudah kadaluarsa.');
        }

        $token = QrClassToken::where('study_group_id', $studyGroupId)
            ->where(function ($q) {
                $q->whereNull('qr_url_expires_at')
                    ->orWhere('qr_url_expires_at', '>', now());
            })
            ->first();

        if (! $token) {
            return $this->scanFailure($request, 'QR tidak valid untuk kelas ini.');
        }

        $token->incrementScanCount();

        $user = $request->user();
        $today = today();
        $dayOfWeek = (int) $today->format('w') === 0 ? 7 : (int) $today->format('w');
        $academicYear = AcademicYear::where('is_active', true)->first();

        // Find the matching jadwal_kbm for this teacher + study group + today
        $jadwalKbm = JadwalKbm::where('teacher_id', $user->id)
            ->where('study_group_id', $studyGroupId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereHas('academicYear', fn ($q) => $q->where('id', $academicYear?->id))
            ->first();

        if (! $jadwalKbm) {
            return $this->scanFailure($request, 'Anda tidak memiliki jadwal mengajar di kelas ini hari ini.');
        }

        // ── School validation — only teachers from same school can scan ──
        $schoolContext = app(OrganizationContext::class);
        if ($schoolContext->hasValidSchool() && $jadwalKbm->school_id !== $schoolContext->schoolId) {
            return $this->scanFailure($request, 'Akses ditolak: jadwal ini bukan dari sekolah Anda.');
        }

        // ── Check existing record ───────────────────────────────────
        $existing = TeacherClassAttendance::where('teacher_id', $user->id)
            ->where('jadwal_kbm_id', $jadwalKbm->id)
            ->where('attendance_date', $today)
            ->first();

        // Already checked out completely — warn about double attendance
        if ($existing && $existing->actual_time_in && $existing->actual_time_out) {
            return $this->scanFailure($request,
                'Anda sudah melakukan absensi lengkap untuk kelas ini hari ini (check-in & check-out). Tidak bisa scan ulang.', [
                    'already_completed' => true,
                    'jadwal_kbm_id' => $jadwalKbm->id,
                    'study_group_name' => $jadwalKbm->studyGroup?->name,
                ]);
        }

        $settings = $this->getSettings();
        $now = now()->format('H:i:s');
        $startTime = $jadwalKbm->start_time;
        $endTime = $jadwalKbm->end_time;

        // If already checked in but not out yet, this is CHECK-OUT
        if ($existing && $existing->actual_time_in && ! $existing->actual_time_out) {
            return $this->processCheckOut($request, $existing, $jadwalKbm, $token, $settings);
        }

        // Otherwise, this is a CHECK-IN
        return $this->processCheckIn($request, $jadwalKbm, $token, $settings, $user);
    }

    /**
     * Process check-in (scan masuk).
     */
    private function processCheckIn(
        Request $request,
        JadwalKbm $jadwalKbm,
        QrClassToken $token,
        array $settings,
        User $user
    ) {
        $now = now();
        $startTime = $jadwalKbm->start_time;
        $toleranceBefore = (int) ($settings['qr_tolerance_before_minutes'] ?? 15);
        $toleranceAfter = (int) ($settings['qr_tolerance_after_minutes'] ?? 5);
        $lateThreshold = (int) ($settings['qr_late_threshold_minutes'] ?? 15);

        $startTs = strtotime($startTime);
        $nowTs = strtotime($now);

        $diffSeconds = $nowTs - $startTs;
        $diffMinutes = (int) round($diffSeconds / 60);

        // Before window start (too early)
        if ($diffMinutes < -$toleranceBefore) {
            return $this->scanFailure($request, "QR terlalu dini. Bisa scan maks. {$toleranceBefore} menit sebelum jam ajaran.");
        }

        // After window end (too late)
        if ($diffMinutes > $toleranceAfter) {
            return $this->scanFailure($request, "QR sudah lewat waktu. Batas maksimal {$toleranceAfter} menit setelah jam ajaran.");
        }

        $isLate = $diffMinutes > 0 && $diffMinutes >= $lateThreshold;
        $lateMinutes = max(0, $diffMinutes);

        $academicYear = AcademicYear::where('is_active', true)->first();

        $attendance = TeacherClassAttendance::create([
            'id' => (string) Str::uuid(),
            'school_id' => $jadwalKbm->school_id,
            'academic_year_id' => $academicYear?->id,
            'study_group_id' => $jadwalKbm->study_group_id,
            'jadwal_kbm_id' => $jadwalKbm->id,
            'teacher_id' => $user->id,
            'qr_token_id' => $token->id,
            'attendance_date' => today(),
            'scheduled_start_time' => $startTime,
            'scheduled_end_time' => $jadwalKbm->end_time,
            'actual_time_in' => $now->format('H:i:s'),
            'late_minutes' => $lateMinutes,
            'status_masuk' => $isLate ? 'terlambat' : 'hadir',
            'status_keluar' => 'belum_keluar',
            'recorded_by' => $user->id,
        ]);

        event(new TeacherQrScanned(
            schoolId: $jadwalKbm->school_id,
            teacherId: $user->id,
            teacherName: $user->name,
            studyGroupCode: $jadwalKbm->studyGroup->code ?? '',
            studyGroupName: $jadwalKbm->studyGroup->name ?? '',
            status: $isLate ? 'terlambat' : 'hadir',
            lateMinutes: $lateMinutes,
            scheduledStartTime: $startTime,
            scheduledEndTime: $jadwalKbm->end_time,
            isSubstitute: $attendance->is_substituted,
        ));

        return $this->scanSuccess($request, [
            'type' => 'check_in',
            'message' => $isLate
                ? "Check-in terlambat {$lateMinutes} menit untuk {$jadwalKbm->studyGroup->name}."
                : "Check-in berhasil untuk {$jadwalKbm->studyGroup->name}.",
            'status' => $isLate ? 'terlambat' : 'hadir',
            'jadwal_kbm_id' => $jadwalKbm->id,
            'study_group_name' => $jadwalKbm->studyGroup?->name,
            'study_group_code' => $jadwalKbm->studyGroup?->code,
            'late_minutes' => $lateMinutes,
            'scheduled_start' => $startTime,
            'scheduled_end' => $jadwalKbm->end_time,
            'action_hint' => 'Scan QR lagi saat jam berakhir untuk check-out.',
        ]);
    }

    /**
     * Process check-out (scan keluar).
     */
    private function processCheckOut(
        Request $request,
        TeacherClassAttendance $attendance,
        JadwalKbm $jadwalKbm,
        QrClassToken $token,
        array $settings
    ) {
        $now = now();
        $endTime = $jadwalKbm->end_time;
        $checkoutWindowBefore = (int) ($settings['qr_checkout_window_before'] ?? 10);
        $checkoutWindowAfter = (int) ($settings['qr_checkout_window_after'] ?? 30);

        $endTs = strtotime($endTime);
        $nowTs = strtotime($now);
        $diffSeconds = $nowTs - $endTs;
        $diffMinutes = (int) round($diffSeconds / 60);

        // Too early to check out
        if ($diffMinutes < -$checkoutWindowBefore) {
            return $this->scanFailure($request, "QR keluar terlalu dini. Bisa scan keluar maks. {$checkoutWindowBefore} menit sebelum jam ajaran selesai.");
        }

        // Too late (past tolerance)
        if ($diffMinutes > $checkoutWindowAfter) {
            return $this->scanFailure($request, "QR keluar sudah lewat batas. Batas maksimal {$checkoutWindowAfter} menit setelah jam ajaran selesai.");
        }

        $earlyLeaveMinutes = max(0, -$diffMinutes); // positive = early leave
        $durationMinutes = (int) round(($nowTs - strtotime($attendance->actual_time_in)) / 60);
        $isEarlyLeave = $earlyLeaveMinutes > 0;

        $attendance->update([
            'actual_time_out' => $now->format('H:i:s'),
            'early_leave_minutes' => $earlyLeaveMinutes,
            'duration_minutes' => $durationMinutes,
            'status_keluar' => $isEarlyLeave ? 'keluar_cepat' : 'selesai',
            'checkout_qr_token_id' => $token->id,
        ]);

        event(new TeacherCheckedOut(
            schoolId: $jadwalKbm->school_id,
            teacherId: $attendance->teacher_id,
            teacherName: $attendance->teacher->name ?? '',
            studyGroupCode: $jadwalKbm->studyGroup?->code ?? '',
            studyGroupName: $jadwalKbm->studyGroup?->name ?? '',
            statusKeluar: $isEarlyLeave ? 'keluar_cepat' : 'selesai',
            earlyLeaveMinutes: $earlyLeaveMinutes,
            durationMinutes: $durationMinutes,
            actualTimeIn: $attendance->actual_time_in,
            actualTimeOut: $now->format('H:i:s'),
        ));

        return $this->scanSuccess($request, [
            'type' => 'check_out',
            'message' => $isEarlyLeave
                ? "Check-out '{$jadwalKbm->studyGroup->name}'. Pulang {$earlyLeaveMinutes} menit sebelum jam selesai."
                : "Check-out berhasil untuk {$jadwalKbm->studyGroup->name}.",
            'status' => $isEarlyLeave ? 'keluar_cepat' : 'selesai',
            'early_leave_minutes' => $earlyLeaveMinutes,
            'duration_minutes' => $durationMinutes,
            'scheduled_end' => $endTime,
            'actual_time_out' => $now->format('H:i:s'),
            'action_hint' => 'Scan QR berikutnya untuk jadwal selanjutnya.',
        ]);
    }

    /**
     * Return JSON response for AJAX scans, back() for HTML fallback.
     */
    private function scanResponse(Request $request, array $data): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($data);
        }
        $key = $data['success'] ? 'success' : 'error';

        return back()->with($key, $data['message'])->withInput();
    }

    private function scanSuccess(Request $request, array $data): JsonResponse|RedirectResponse
    {
        return $this->scanResponse($request, array_merge(['success' => true], $data));
    }

    private function scanFailure(Request $request, string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        return $this->scanResponse($request, array_merge(['success' => false, 'message' => $message], $extra));
    }

    /**
     * Show attendance history with optional date/status filters.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $start = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $end = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));
        $statusFilter = $request->input('status');

        // Permission-based scoping — same logic as exportHistory()
        $hasFullReportExport = canPermission('teacher-attendance_report_export');

        $query = TeacherClassAttendance::with(['jadwalKbm.studyGroup', 'jadwalKbm.subject'])
            ->whereBetween('attendance_date', [$start, $end]);

        if (! $hasFullReportExport) {
            $query->where('teacher_id', $user->id);
        }

        if ($statusFilter) {
            match ($statusFilter) {
                'hadir' => $query->where('status_masuk', 'hadir'),
                'terlambat' => $query->where('status_masuk', 'terlambat'),
                'belum_keluar' => $query->where('status_keluar', 'belum_keluar'),
                'keluar_cepat' => $query->where('status_keluar', 'keluar_cepat'),
                default => null,
            };
        }

        $records = $query->orderByDesc('attendance_date')->orderBy('jadwal_kbm_id')->get();

        return view('teacher.qr.scan.history', compact('records', 'start', 'end', 'statusFilter'));
    }

    /**
     * Manual check-out for teachers who forgot to scan out.
     * Requires teacher-attendance_view permission.
     */
    public function manualCheckout(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'attendance_id' => 'required|uuid|exists:teacher_class_attendances,id',
            'checkout_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance = TeacherClassAttendance::findOrFail($request->attendance_id);
        $now = now()->setTimeFromTimeString($request->checkout_time);

        $endTime = $attendance->scheduled_end_time;
        $endTs = strtotime($endTime);
        $nowTs = strtotime($now);
        $earlyLeaveMinutes = max(0, (int) round(($endTs - $nowTs) / 60));
        $durationMinutes = max(0, (int) round(($nowTs - strtotime($attendance->actual_time_in)) / 60));

        $attendance->update([
            'actual_time_out' => $now->format('H:i:s'),
            'early_leave_minutes' => $earlyLeaveMinutes,
            'duration_minutes' => $durationMinutes,
            'status_keluar' => $earlyLeaveMinutes > 0 ? 'keluar_cepat' : 'selesai',
            'notes' => $request->notes,
            'verified_by_waka' => $request->user()->id,
            'verified_by_waka_at' => now(),
        ]);

        $jadwalKbm = JadwalKbm::find($attendance->jadwal_kbm_id);

        event(new TeacherCheckedOut(
            schoolId: (string) $jadwalKbm?->school_id ?? '',
            teacherId: (string) $attendance->teacher_id,
            teacherName: $attendance->teacher?->name ?? '',
            studyGroupCode: $jadwalKbm?->studyGroup?->code ?? '',
            studyGroupName: $jadwalKbm?->studyGroup?->name ?? '',
            statusKeluar: $earlyLeaveMinutes > 0 ? 'keluar_cepat' : 'selesai',
            earlyLeaveMinutes: $earlyLeaveMinutes,
            durationMinutes: $durationMinutes,
            actualTimeIn: $attendance->actual_time_in ?? '',
            actualTimeOut: $now->format('H:i:s'),
        ));

        return back()->with('success', 'Check-out berhasil dicatat.');
    }

    /**
     * Manual check-in by Waka for a teacher who couldn't scan.
     * Requires teacher-attendance_manual permission (Waka-only).
     */
    public function manualCheckin(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'teacher_id' => 'required|uuid|exists:users,id',
            'jadwal_kbm_id' => 'required|uuid|exists:jadwal_kbms,id',
            'checkin_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $jadwalKbm = JadwalKbm::findOrFail($request->jadwal_kbm_id);
        $targetTeacher = User::findOrFail($request->teacher_id);

        // School validation — only teachers from the same school
        $schoolContext = app(OrganizationContext::class);
        if ($schoolContext->hasValidSchool() && $jadwalKbm->school_id !== $schoolContext->schoolId) {
            return back()->with('error', 'Gagal: jadwal bukan milik sekolah Anda saat ini.');
        }

        $today = today();

        // Double-check: must not already exist today
        $existing = TeacherClassAttendance::where('teacher_id', $targetTeacher->id)
            ->where('jadwal_kbm_id', $jadwalKbm->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existing) {
            return back()->with('error', 'Guru ini sudah memiliki catatan absensi untuk jadwal ini hari ini.');
        }

        $checkinTime = Carbon::parse($request->checkin_time.':00');
        $startTime = $jadwalKbm->start_time;
        $toleranceAfter = (int) ($this->getSettings()['qr_tolerance_after_minutes'] ?? 5);
        $lateThreshold = (int) ($this->getSettings()['qr_late_threshold_minutes'] ?? 15);

        $nowTs = strtotime($checkinTime);
        $startTs = strtotime($startTime);
        $diffMinutes = (int) round(($nowTs - $startTs) / 60);
        $isLate = $diffMinutes >= $lateThreshold;
        $lateMinutes = max(0, $diffMinutes);

        $academicYear = AcademicYear::where('is_active', true)->first();

        $attendance = TeacherClassAttendance::create([
            'id' => (string) Str::uuid(),
            'school_id' => $jadwalKbm->school_id,
            'academic_year_id' => $academicYear?->id,
            'study_group_id' => $jadwalKbm->study_group_id,
            'jadwal_kbm_id' => $jadwalKbm->id,
            'teacher_id' => $targetTeacher->id,
            'qr_token_id' => null,
            'attendance_date' => $today,
            'scheduled_start_time' => $startTime,
            'scheduled_end_time' => $jadwalKbm->end_time,
            'actual_time_in' => $checkinTime->format('H:i:s'),
            'late_minutes' => $lateMinutes,
            'status_masuk' => $isLate ? 'terlambat' : 'hadir',
            'status_keluar' => 'belum_keluar',
            'recorded_by' => $user->id,
            'notes' => $request->notes,
        ]);

        event(new TeacherQrScanned(
            schoolId: $jadwalKbm->school_id,
            teacherId: $targetTeacher->id,
            teacherName: $targetTeacher->name,
            studyGroupCode: $jadwalKbm->studyGroup->code ?? '',
            studyGroupName: $jadwalKbm->studyGroup->name ?? '',
            status: $isLate ? 'terlambat' : 'hadir',
            lateMinutes: $lateMinutes,
            scheduledStartTime: $startTime,
            scheduledEndTime: $jadwalKbm->end_time,
            isSubstitute: $attendance->is_substituted,
        ));

        return back()->with('success',
            "Check-in manual berhasil untuk {$targetTeacher->name} ({$jadwalKbm->studyGroup->name})"
            .($isLate ? " — terlambat {$lateMinutes} menit" : '.')
        );
    }

    /**
     * Waka dashboard — live view of teacher QR attendance.
     * Requires teacher-attendance_view permission.
     */
    public function wakaDashboard(Request $request)
    {
        $user = $request->user();
        $today = today();
        $dayOfWeek = (int) $today->format('w') === 0 ? 7 : (int) $today->format('w');
        $academicYear = AcademicYear::where('is_active', true)->first();

        // All active jadwal for today
        $schedules = JadwalKbm::where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('academicYear', fn ($q) => $q->where('id', $academicYear?->id))
            ->with(['teacher:id,name', 'studyGroup:name,code', 'subject:name'])
            ->orderBy('slot_index')
            ->get();

        // Today's attendances
        $attendances = TeacherClassAttendance::where('attendance_date', $today)
            ->with(['teacher:id,name', 'jadwalKbm.studyGroup:name,code'])
            ->get()
            ->keyBy(fn ($a) => $a->teacher_id.'|'.$a->jadwal_kbm_id);

        // Teachers who haven't checked out yet
        $notCheckedOut = TeacherClassAttendance::where('attendance_date', $today)
            ->where('status_keluar', 'belum_keluar')
            ->whereNull('actual_time_out')
            ->with(['teacher:name', 'jadwalKbm.studyGroup:name,code', 'jadwalKbm:end_time'])
            ->get();

        // Not present today
        $notPresent = $schedules->filter(function ($jadwal) use ($attendances) {
            $key = $jadwal->teacher_id.'|'.$jadwal->id;

            return ! isset($attendances[$key]);
        });

        // School ID for broadcast channel — use first schedule's study group if available
        $schoolId = $schedules->first()?->studyGroup?->school_id ?? (string) $request->user()->school_id;

        return view('teacher.qr.scan.waka-dashboard', compact(
            'schedules',
            'attendances',
            'notCheckedOut',
            'notPresent',
            'schoolId'
        ));
    }

    /**
     * Export attendance history to Excel.
     * GET /teacher/qr/history/export
     */
    public function exportHistory(Request $request)
    {
        $start = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', today()->format('Y-m-d'));

        $user = $request->user();

        // Inline permission check:
        // - Can have full report export (all teachers)
        // - Otherwise (guru biasa): only own records
        $hasFullReportExport = canPermission('teacher-attendance_report_export');

        $query = TeacherClassAttendance::with(['teacher', 'jadwalKbm.studyGroup', 'jadwalKbm.subject'])
            ->whereBetween('attendance_date', [$start, $end]);

        if (! $hasFullReportExport) {
            $query->where('teacher_id', $user->id);
        }

        $records = $query->orderBy('attendance_date', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['Tanggal', 'Nama Guru', 'NIS/NISN', 'Kelas', 'Mata Pelajaran',
            'Jadwal Masuk', 'Jadwal Keluar', 'Waktu Masuk', 'Status Masuk', 'Waktu Keluar',
            'Status Keluar', 'Terlambat (mnt)', 'Pulang Cepat (mnt)', 'Durasi (mnt)'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($records as $r) {
            $scheduledStart = $r->jadwalKbm ? ($r->jadwalKbm->start_time ?? '') : '';
            $scheduledEnd = $r->jadwalKbm ? ($r->jadwalKbm->end_time ?? '') : '';
            $sheet->setCellValue('A'.$row, $r->attendance_date?->format('Y-m-d') ?? '');
            $sheet->setCellValue('B'.$row, $r->teacher?->name ?? '-');
            $sheet->setCellValue('C'.$row, $r->teacher?->nisn ?? '-');
            $sheet->setCellValue('D'.$row, $r->jadwalKbm?->studyGroup?->name ?? '-');
            $sheet->setCellValue('E'.$row, $r->jadwalKbm?->subject?->name ?? '-');
            $sheet->setCellValue('F'.$row, $scheduledStart);
            $sheet->setCellValue('G'.$row, $scheduledEnd);
            $sheet->setCellValue('H'.$row, $r->actual_time_in ?? '-');
            $sheet->setCellValue('I'.$row, $r->status_masuk === 'terlambat' ? 'Terlambat' : 'Hadir');
            $sheet->setCellValue('J'.$row, $r->actual_time_out ?? '-');
            $sheet->setCellValue('K'.$row, $r->status_keluar === 'keluar_cepat' ? 'Pulang Cepat' : ($r->actual_time_out ? 'Tuntas' : 'Belum'));
            $sheet->setCellValue('L'.$row, $r->late_minutes ?? 0);
            $sheet->setCellValue('M'.$row, $r->early_leave_minutes ?? 0);
            $sheet->setCellValue('N'.$row, $r->duration_minutes ?? 0);
            $row++;
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'absensi-guru-'.$start.'-'.$end.'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Manual check-in form for administrators.
     * Accessible by roles with teacher-attendance_manual permission.
     */
    public function manualIndex(Request $request)
    {
        $user = $request->user();
        $academicYear = AcademicYear::where('is_active', true)->first();
        $today = today();
        $dayOfWeek = (int) $today->format('w') === 0 ? 7 : (int) $today->format('w');

        $allTeachers = User::where('school_id', $user->school_id)
            ->where('role_id', function ($q) {
                $q->select('id')->from('roles')->whereIn('name', ['Guru', 'Guru Tahfidz', 'Coordinator Guru', 'Departemen Tahfidz']);
            })
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();

        $schedules = JadwalKbm::where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('academicYear', fn ($q) => $q->where('id', $academicYear?->id))
            ->with(['teacher:id,name', 'studyGroup:name,code', 'subject:name'])
            ->orderBy('slot_index')
            ->get();

        $todayAttendances = TeacherClassAttendance::where('attendance_date', $today)
            ->with(['teacher:id,name'])
            ->get()
            ->keyBy(fn ($a) => $a->teacher_id);

        return view('teacher.qr.scan.manual', compact(
            'allTeachers',
            'schedules',
            'todayAttendances',
            'academicYear'
        ));
    }

    /**
     * Process manual check-in submission.
     * Requires teacher-attendance_manual permission.
     */
    public function manualStore(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'teacher_id' => 'required|uuid|exists:users,id',
            'jadwal_kbm_id' => 'required|uuid|exists:jadwal_kbms,id',
            'checkin_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $jadwalKbm = JadwalKbm::findOrFail($request->jadwal_kbm_id);
        $targetTeacher = User::findOrFail($request->teacher_id);

        $schoolContext = app(OrganizationContext::class);
        if ($schoolContext->hasValidSchool() && $jadwalKbm->school_id !== $schoolContext->schoolId) {
            return back()->with('error', 'Gagal: jadwal bukan milik sekolah Anda saat ini.');
        }

        $today = today();

        $existing = TeacherClassAttendance::where('teacher_id', $targetTeacher->id)
            ->where('jadwal_kbm_id', $jadwalKbm->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existing) {
            return back()->with('error', 'Guru ini sudah memiliki catatan absensi untuk jadwal ini hari ini.');
        }

        $checkinTime = Carbon::parse($request->checkin_time.':00');
        $startTime = $jadwalKbm->start_time;
        $toleranceAfter = (int) ($this->getSettings()['qr_tolerance_after_minutes'] ?? 5);
        $lateThreshold = (int) ($this->getSettings()['qr_late_threshold_minutes'] ?? 15);

        $nowTs = strtotime($checkinTime);
        $startTs = strtotime($startTime);
        $diffMinutes = (int) round(($nowTs - $startTs) / 60);
        $isLate = $diffMinutes >= $lateThreshold;
        $lateMinutes = max(0, $diffMinutes);

        $academicYear = AcademicYear::where('is_active', true)->first();

        $attendance = TeacherClassAttendance::create([
            'id' => (string) Str::uuid(),
            'school_id' => $jadwalKbm->school_id,
            'academic_year_id' => $academicYear?->id,
            'study_group_id' => $jadwalKbm->study_group_id,
            'jadwal_kbm_id' => $jadwalKbm->id,
            'teacher_id' => $targetTeacher->id,
            'qr_token_id' => null,
            'attendance_date' => $today,
            'scheduled_start_time' => $startTime,
            'scheduled_end_time' => $jadwalKbm->end_time,
            'actual_time_in' => $checkinTime->format('H:i:s'),
            'late_minutes' => $lateMinutes,
            'status_masuk' => $isLate ? 'terlambat' : 'hadir',
            'status_keluar' => 'belum_keluar',
            'recorded_by' => $user->id,
            'notes' => $request->notes,
        ]);

        event(new TeacherQrScanned(
            schoolId: $jadwalKbm->school_id,
            teacherId: $targetTeacher->id,
            teacherName: $targetTeacher->name,
            studyGroupCode: $jadwalKbm->studyGroup?->code ?? '',
            studyGroupName: $jadwalKbm->studyGroup?->name ?? '',
            status: $isLate ? 'terlambat' : 'hadir',
            lateMinutes: $lateMinutes,
            scheduledStartTime: $startTime,
            scheduledEndTime: $jadwalKbm->end_time,
            isSubstitute: $attendance->is_substituted,
        ));

        return back()->with('success',
            "Check-in manual berhasil untuk {$targetTeacher->name} ({$jadwalKbm->studyGroup?->name})"
            .($isLate ? " — terlambat {$lateMinutes} menit" : '.')
        );
    }

    private function getSettings(): array
    {
        $defaultSettings = [
            'qr_tolerance_before_minutes' => 15,
            'qr_tolerance_after_minutes' => 5,
            'qr_late_threshold_minutes' => 15,
            'qr_checkout_window_before' => 10,
            'qr_checkout_window_after' => 30,
            'qr_early_leave_threshold_minutes' => 10,
        ];
        $overrides = AbsensiGtkSetting::get('qr_settings', []) ?? [];

        return array_merge($defaultSettings, is_array($overrides) ? $overrides : []);
    }
}
