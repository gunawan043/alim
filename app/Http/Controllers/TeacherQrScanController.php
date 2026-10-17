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
use Illuminate\Support\Facades\URL;
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
            ->with(['studyGroup' => fn ($q) => $q->with('gradeLevel'), 'subject:id,name'])
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

        // Kelompokkan jadwal hari ini untuk wizard tab (mirip scan asrama).
        $belumAbsen = $schedules->filter(fn ($s) => ! isset($attendances[$s->id]))->values();

        $berlangsung = $schedules->filter(function ($s) use ($attendances) {
            $att = $attendances[$s->id] ?? null;

            return $att && $att->actual_time_in && ! $att->actual_time_out;
        })->values();

        $selesaiHariIni = $schedules->filter(function ($s) use ($attendances) {
            $att = $attendances[$s->id] ?? null;

            return $att && $att->actual_time_out;
        })->values();

        $stats = [
            'total' => $schedules->count(),
            'checked_in' => $schedules->filter(fn ($s) => isset($attendances[$s->id]) && $attendances[$s->id]->actual_time_in)->count(),
            'checked_out' => $schedules->filter(fn ($s) => isset($attendances[$s->id]) && $attendances[$s->id]->actual_time_out)->count(),
            'late' => $schedules->filter(fn ($s) => isset($attendances[$s->id]) && $attendances[$s->id]->status_masuk === 'terlambat')->count(),
            'pending' => $schedules->count() - $schedules->filter(fn ($s) => isset($attendances[$s->id]))->count(),
        ];

        $recentRecords = TeacherClassAttendance::where('teacher_id', $user->id)
            ->where('attendance_date', '>=', $today->copy()->subDays(6))
            ->with(['jadwalKbm.studyGroup', 'jadwalKbm.subject'])
            ->orderByDesc('attendance_date')
            ->limit(5)
            ->get();

        // URL bertanda tangan per kelas hari ini — dipakai scanner in-app
        // (endpoint tetap memvalidasi signature, sehingga aman dari request manual).
        $scanUrls = $schedules
            ->pluck('study_group_id')
            ->unique()
            ->mapWithKeys(fn ($studyGroupId) => [
                $studyGroupId => URL::temporarySignedRoute(
                    'user.teacher-qr.scan.process',
                    now()->addHours(12),
                    ['userId' => $user->id, 'study_group_id' => $studyGroupId]
                ),
            ])
            ->all();

        $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

        // Kelas yang sedang berlangsung (jika ada) & kelas berikutnya — untuk spotlight.
        $currentClass = $berlangsung->first();
        $nextClass = $belumAbsen->first();
        $completion = $stats['total'] > 0 ? (int) round(($stats['checked_out'] / $stats['total']) * 100) : 0;

        $geo = app(\App\Services\QrAttendanceSettings::class)->geoSettings();

        return view('teacher.qr.scan.index', compact(
            'geo',
            'schedules',
            'attendances',
            'needsCheckout',
            'academicYear',
            'stats',
            'recentRecords',
            'scanUrls',
            'belumAbsen',
            'berlangsung',
            'selesaiHariIni',
            'currentClass',
            'nextClass',
            'completion'
        ))->with('userId', $user->id)
            ->with('today', $today)
            ->with('dayName', $dayNames[$dayOfWeek] ?? '-');
    }

    /**
     * Process QR scan (check-in or check-out).
     * Accepts signed URL from QR code.
     */
    public function scanProcess(Request $request, string $userId, string $studyGroupId)
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
            return $this->scanFailure($request, 'Anda tidak memiliki jadwal mengajar di kelas ini hari ini. Pastikan kelas yang Anda pilih sesuai jadwal.', [], 'info');
        }

        // Geofence opsional (diatur per satuan): validasi lokasi di BACKEND.
        // Koordinat hanya dipakai untuk keputusan — tidak disimpan.
        $geoStatus = app(\App\Services\QrAttendanceSettings::class)->geofenceStatus(
            $request->filled('lat') ? (float) $request->input('lat') : null,
            $request->filled('lng') ? (float) $request->input('lng') : null,
            $request->filled('accuracy') ? (float) $request->input('accuracy') : null,
        );

        if ($geoStatus['required'] && ! $geoStatus['ok']) {
            return $this->scanFailure($request, $geoStatus['message'] ?? 'Lokasi Anda di luar area yang diizinkan.', [], 'warning');
        }

        // Opsi B (QR cetak): kunci perangkat opsional — mengurangi titip absen.
        $deviceStatus = app(\App\Services\TeacherDeviceBinding::class)->verify(
            $request->user(),
            $request->input('device_id'),
            $request->userAgent(),
        );

        if (! $deviceStatus['ok']) {
            return $this->scanFailure($request, $deviceStatus['message'] ?? 'Perangkat tidak dikenali.', [], 'error');
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
                ], 'info');
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
            return $this->scanFailure($request, "Jam pelajaran belum dimulai. Bisa scan maksimal {$toleranceBefore} menit sebelum jam ajaran.", [], 'info');
        }

        // After window end (too late)
        if ($diffMinutes > $toleranceAfter) {
            return $this->scanFailure($request, "Sudah lewat batas waktu scan (maksimal {$toleranceAfter} menit setelah jam ajaran).", [], 'warning');
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

        // Notifikasi keterlambatan ke petugas satuan (Waka/Kepala/TU/Kurikulum).
        if ($isLate) {
            $this->notifyLateAttendance($jadwalKbm, $user, $attendance, $lateMinutes, $startTime);
        }

        $this->safeEvent(new TeacherQrScanned(
            schoolId: $jadwalKbm->school_id,
            teacherId: $user->id,
            teacherName: $user->name,
            studyGroupCode: $jadwalKbm->studyGroup->code ?? '',
            studyGroupName: $jadwalKbm->studyGroup->name ?? '',
            status: $isLate ? 'terlambat' : 'hadir',
            lateMinutes: $lateMinutes,
            scheduledStartTime: $startTime,
            scheduledEndTime: $jadwalKbm->end_time,
            isSubstitute: (bool) ($attendance->is_substituted ?? false),
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
            return $this->scanFailure($request, "Belum waktunya scan keluar. Bisa scan keluar maksimal {$checkoutWindowBefore} menit sebelum jam ajaran selesai.", [], 'info');
        }

        // Too late (past tolerance)
        if ($diffMinutes > $checkoutWindowAfter) {
            return $this->scanFailure($request, "Sudah lewat batas scan keluar (maksimal {$checkoutWindowAfter} menit setelah jam ajaran selesai).", [], 'warning');
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

        $this->safeEvent(new TeacherCheckedOut(
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
        // `outcome` dipakai front-end untuk memilih modal (success/error/warning/info).
        return $this->scanResponse($request, array_merge(['success' => true, 'outcome' => 'success'], $data));
    }

    private function scanFailure(Request $request, string $message, array $extra = [], string $outcome = 'error'): JsonResponse|RedirectResponse
    {
        return $this->scanResponse($request, array_merge([
            'success' => false,
            'outcome' => in_array($outcome, ['error', 'warning', 'info'], true) ? $outcome : 'error',
            'message' => $message,
        ], $extra));
    }

    /**
     * Notifikasi in-app: GTK terlambat masuk kelas (dikirim ke satuan kerja sekolah).
     */
    private function notifyLateAttendance(JadwalKbm $jadwalKbm, $user, TeacherClassAttendance $attendance, int $lateMinutes, string $startTime): void
    {
        try {
            $workUnitId = \App\Models\School::find($jadwalKbm->school_id)?->work_unit_id;

            $notifier = app(\App\Services\NotificationUniversalService::class);
            $group = $jadwalKbm->studyGroup?->name ?? '-';
            $subject = $jadwalKbm->subject?->name ?? 'Jam Pelajaran';

            // Pemberitahuan ke satuan kerja (in-app), kecuali guru yang bersangkutan.
            $notifier->notifyGtk($workUnitId, [
                'module' => 'teacher-attendance',
                'reference_type' => 'teacher_class_attendance',
                'reference_id' => $attendance->id,
                'type' => 'warning',
                'action' => 'teacher_late',
                'title' => 'GTK terlambat: '.($user->name ?? 'GTK'),
                'message' => 'Terlambat '.$lateMinutes.' menit pada '.$group.' (jadwal '.$startTime.').',
            ], $user->id);

            // Konfirmasi langsung ke guru (in-app + channel sesuai preferensi).
            $prefs = \App\Models\NotificationPreference::forUser($user);

            if ($prefs->late_notification) {
                $notifier->send($user->id, [
                    'module' => 'teacher-attendance',
                    'reference_type' => 'teacher_class_attendance',
                    'reference_id' => $attendance->id,
                    'type' => 'warning',
                    'action' => 'teacher_late',
                    'title' => 'Anda tercatat terlambat '.$lateMinutes.' menit',
                    'message' => 'Jam '.$subject.' di '.$group.' (jadwal '.$startTime.') tercatat terlambat '.$lateMinutes.' menit. Segera masuk kelas.',
                    'action_url' => route('user.teacher-qr.scan', ['userId' => $user->id]),
                    'action_text' => 'Buka Scan QR',
                    'priority' => 'high',
                    'send_email' => $prefs->email_enabled,
                    'send_whatsapp' => $prefs->whatsapp_enabled,
                    'send_push' => $prefs->push_enabled,
                    'template_params' => [$subject, $group, $startTime, (string) $lateMinutes],
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
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
        $search = trim((string) $request->input('q'));

        // Permission-based scoping — same logic as exportHistory()
        $hasFullReportExport = canPermission('teacher-attendance_report_export');

        $base = TeacherClassAttendance::query()
            ->whereDate('attendance_date', '>=', $start)
            ->whereDate('attendance_date', '<=', $end);

        if (! $hasFullReportExport) {
            $base->where('teacher_id', $user->id);
        }

        if ($statusFilter) {
            match ($statusFilter) {
                'hadir' => $base->where('status_masuk', 'hadir'),
                'terlambat' => $base->where('status_masuk', 'terlambat'),
                'belum_keluar' => $base->where('status_keluar', 'belum_keluar'),
                'keluar_cepat' => $base->where('status_keluar', 'keluar_cepat'),
                default => null,
            };
        }

        if ($search !== '') {
            $base->where(function ($w) use ($search) {
                $w->whereHas('teacher', fn ($t) => $t->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('jadwalKbm.studyGroup', fn ($g) => $g->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('jadwalKbm.subject', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        // Ringkasan dihitung dari seluruh hasil filter (bukan hanya halaman aktif).
        $stats = [
            'total' => (clone $base)->count(),
            'hadir' => (clone $base)->where('status_masuk', 'hadir')->count(),
            'terlambat' => (clone $base)->where('status_masuk', 'terlambat')->count(),
            'belum_keluar' => (clone $base)->where('status_keluar', 'belum_keluar')->count(),
            'keluar_cepat' => (clone $base)->where('status_keluar', 'keluar_cepat')->count(),
            'avg_late' => (int) round((float) ((clone $base)->avg('late_minutes') ?? 0)),
        ];

        $records = (clone $base)
            ->with(['teacher', 'jadwalKbm.teacher', 'jadwalKbm.studyGroup.gradeLevel', 'jadwalKbm.subject'])
            ->orderByDesc('attendance_date')
            ->orderBy('scheduled_start_time')
            ->paginate(20)
            ->withQueryString();

        return view('teacher.qr.scan.history', compact(
            'records', 'start', 'end', 'statusFilter', 'search', 'stats', 'hasFullReportExport',
        ));
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

        $this->safeEvent(new TeacherCheckedOut(
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

        $this->safeEvent(new TeacherQrScanned(
            schoolId: $jadwalKbm->school_id,
            teacherId: $targetTeacher->id,
            teacherName: $targetTeacher->name,
            studyGroupCode: $jadwalKbm->studyGroup->code ?? '',
            studyGroupName: $jadwalKbm->studyGroup->name ?? '',
            status: $isLate ? 'terlambat' : 'hadir',
            lateMinutes: $lateMinutes,
            scheduledStartTime: $startTime,
            scheduledEndTime: $jadwalKbm->end_time,
            isSubstitute: (bool) ($attendance->is_substituted ?? false),
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
            ->whereDate('attendance_date', '>=', $start)
            ->whereDate('attendance_date', '<=', $end);

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
        $schoolId = $request->attributes->get('schoolContextId');
        $academicYear = AcademicYear::where('is_active', true)->first();
        $today = today();
        $dayOfWeek = (int) $today->format('w') === 0 ? 7 : (int) $today->format('w');

        // Roster guru: snapshot permission → data kepegawaian (fallback).
        $teacherIds = app(\App\Services\TeacherRosterService::class)->idsForSchool($schoolId);

        $allTeachers = User::whereIn('id', $teacherIds)
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();

        $schedules = JadwalKbm::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('academicYear', fn ($q) => $q->where('id', $academicYear?->id))
            ->with(['teacher:id,name', 'studyGroup:name,code', 'subject:name'])
            ->orderBy('slot_index')
            ->get();

        $todayAttendances = TeacherClassAttendance::where('attendance_date', $today)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
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

        $this->safeEvent(new TeacherQrScanned(
            schoolId: $jadwalKbm->school_id,
            teacherId: $targetTeacher->id,
            teacherName: $targetTeacher->name,
            studyGroupCode: $jadwalKbm->studyGroup?->code ?? '',
            studyGroupName: $jadwalKbm->studyGroup?->name ?? '',
            status: $isLate ? 'terlambat' : 'hadir',
            lateMinutes: $lateMinutes,
            scheduledStartTime: $startTime,
            scheduledEndTime: $jadwalKbm->end_time,
            isSubstitute: (bool) ($attendance->is_substituted ?? false),
        ));

        return back()->with('success',
            "Check-in manual berhasil untuk {$targetTeacher->name} ({$jadwalKbm->studyGroup?->name})"
            .($isLate ? " — terlambat {$lateMinutes} menit" : '.')
        );
    }

    private function getSettings(): array
    {
        // Sumber tunggal: QrAttendanceSettings (default toleransi terlambat = 5 menit).
        return app(\App\Services\QrAttendanceSettings::class)->timeSettings();
    }

    /**
     * Kirim event broadcast tanpa menggagalkan proses scan.
     * Data absensi sudah tersimpan; kegagalan broadcast (mis. Pusher tidak
     * tersedia) hanya dicatat, tidak mengembalikan 500 ke guru.
     */
    private function safeEvent(object $event): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
