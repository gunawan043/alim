<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\WaliRegistrationToken;
use App\Models\WaliSantri;
use App\Services\Boarding\HealthWorkflowService;
use App\Services\Boarding\LeaveWorkflowService;
use App\Services\Boarding\VisitWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Wali Santri Portal.
 *
 * Wali masuk via access_token (issued by admin via WaliRegistrationToken flow),
 * then dapat:
 *   - Lihat daftar anak yang diasuh
 *   - Lihat status permohonan mereka
 *   - Submit izin pulang / penjengukan / izin sakit
 *
 * Token disimpan sebagai SHA-256 hash di tabel wali_santri.access_token.
 * Setelah token berhasil divalidasi, wali di-`Auth::loginUsingId()` supaya
 * semua workflow service yang pakai Auth::id() / auth()->id() otomatis
 * mencatat wali sebagai creator.
 */
class GuardianPortalController extends Controller
{
    public function __construct(
        private readonly LeaveWorkflowService $leave,
        private readonly VisitWorkflowService $visits,
        private readonly HealthWorkflowService $health,
    ) {}

    /**
     * Landing page / dashboard wali (setelah token valid).
     * URL: /portal/{token}
     */
    public function dashboard(string $token)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }

        // Login sebagai wali untuk session ini (gated by middleware nanti jika perlu)
        Auth::loginUsingId($wali->user_id);

        $students = WaliSantri::with(['student.dormitory'])
            ->where('user_id', $wali->user_id)
            ->active()
            ->get();

        // Recent permits across all children (latest 10)
        $recentLeaveIds = $students->pluck('student_id');
        $recentLeave = \App\Models\DormitoryPermit::whereIn('student_id', $recentLeaveIds)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $recentVisits = \App\Models\DormitoryVisitLog::whereIn('student_id', $recentLeaveIds)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $recentHealth = \App\Models\StudentHealthPermit::whereIn('student_id', $recentLeaveIds)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('portal.dashboard', [
            'wali' => $wali,
            'students' => $students,
            'recentLeave' => $recentLeave,
            'recentVisits' => $recentVisits,
            'recentHealth' => $recentHealth,
            'token' => $token,
        ]);
    }

    /**
     * Halaman pusat notifikasi wali.
     * URL: /portal/{token}/notifications
     */
    public function notifications(string $token)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }

        $notifications = \App\Models\NotificationUniversal::where('user_id', $wali->user_id)
            ->where('is_archived', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = \App\Models\NotificationUniversal::where('user_id', $wali->user_id)
            ->where('is_read', false)
            ->where('is_archived', false)
            ->count();

        return view('portal.notifications', [
            'wali' => $wali,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'token' => $token,
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca.
     * URL: /portal/{token}/notifications/{id}/read
     */
    public function markRead(string $token, string $id)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }

        \App\Models\NotificationUniversal::where('user_id', $wali->user_id)
            ->where('id', $id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return redirect()->route('portal.notifications', ['token' => $token])
            ->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Tandai semua notifikasi sudah dibaca.
     * URL: /portal/{token}/notifications/read-all
     */
    public function markAllRead(string $token)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }

        \App\Models\NotificationUniversal::where('user_id', $wali->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return redirect()->route('portal.notifications', ['token' => $token])
            ->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Halaman Timeline wali: kronologi izin pulang/visit/health per anak.
     * URL: /portal/{token}/timeline
     */
    public function timeline(string $token, Request $request)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }

        $studentId = $request->query('student_id');
        $waliStudents = WaliSantri::with(['student.dormitory'])
            ->where('user_id', $wali->user_id)
            ->active()
            ->get();

        // Validate requested student_id is one of the wali's students
        if ($studentId) {
            $allowed = $waliStudents->where('student_id', (int) $studentId)->first();
            if (! $allowed) {
                abort(403, 'Anda tidak memiliki akses untuk siswa ini.');
            }
        } elseif ($waliStudents->isNotEmpty()) {
            $studentId = $waliStudents->first()->student_id;
        }

        $timeline = collect();
        $selectedStudent = null;

        if ($studentId) {
            $selectedStudent = $waliStudents->where('student_id', (int) $studentId)->first()?->student;

            // Aggregate three workflows into chronological timeline
            $permits = \App\Models\DormitoryPermit::where('student_id', $studentId)->get()->map(fn ($p) => [
                'kind' => 'leave',
                'date' => $p->created_at,
                'title' => "Izin Pulang #{$p->permit_code}",
                'subtitle' => $p->start_date?->format('d M Y').' – '.$p->end_date?->format('d M Y'),
                'status' => $p->status,
                'note' => $p->notes,
            ]);

            $visits = \App\Models\DormitoryVisitLog::where('student_id', $studentId)->get()->map(fn ($v) => [
                'kind' => 'visit',
                'date' => $v->created_at,
                'title' => 'Penjengukan',
                'subtitle' => $v->visit_date?->format('d M Y H:i'),
                'status' => $v->status,
                'note' => $v->purpose,
            ]);

            $health = \App\Models\StudentHealthPermit::where('student_id', $studentId)->get()->map(fn ($h) => [
                'kind' => 'health',
                'date' => $h->created_at,
                'title' => "Izin Sakit ({$h->permit_type})",
                'subtitle' => $h->start_date?->format('d M Y')." ({$h->rest_days} hari)",
                'status' => $h->status,
                'note' => $h->approval_note,
            ]);

            $timeline = collect()
                ->merge($permits)
                ->merge($visits)
                ->merge($health)
                ->sortByDesc('date')
                ->values();
        }

        return view('portal.timeline', [
            'wali' => $wali,
            'waliStudents' => $waliStudents,
            'selectedStudent' => $selectedStudent,
            'timeline' => $timeline,
            'token' => $token,
        ]);
    }

    /**
     * Submit izin pulang untuk salah satu anak.
     */
    public function submitLeave(Request $request, string $token)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }
        Auth::loginUsingId($wali->user_id);

        $data = $request->validate([
            'student_id' => 'required|uuid',
            'permit_type' => 'required|in:sakit,keluarga,libur,acara,izin_khusus',
            'departure_datetime' => 'required|date',
            'expected_return_at' => 'required|date|after:departure_datetime',
            'destination' => 'required|string|max:255',
            'reason' => 'required|string|max:500',
            'mahrom_id' => 'nullable|uuid',
            'companion_name' => 'nullable|string|max:255',
            'companion_phone' => 'nullable|string|max:20',
        ]);

        // Authorization: wali hanya boleh untuk student_id di bawah asuhannya
        $this->authorizeWaliAccess($wali, $data['student_id']);

        $student = Student::findOrFail($data['student_id']);
        $activeYear = $student->academic_year_id ?? \App\Models\AcademicYear::active()?->id;

        $permit = $this->leave->submit($data, $student->dormitory_id, $activeYear);

        return redirect()->route('portal.dashboard', ['token' => $token])
            ->with('success', 'Permohonan izin pulang telah dikirim dan menunggu persetujuan.');
    }

    /**
     * Submit permohonan penjengukan.
     */
    public function submitVisit(Request $request, string $token)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }
        Auth::loginUsingId($wali->user_id);

        $data = $request->validate([
            'student_id' => 'required|uuid',
            'visitor_name' => 'required|string|max:255',
            'visitor_relation' => 'required|string|max:100',
            'visitor_phone' => 'required|string|max:20',
            'visitor_id_type' => 'nullable|in:ktp,sim,passport',
            'visitor_id_number' => 'nullable|string|max:50',
            'visit_from' => 'required|date',
            'visit_until' => 'required|date|after:visit_from',
            'purpose' => 'required|string|max:500',
            'visitor_count' => 'required|integer|min:1|max:5',
        ]);

        $this->authorizeWaliAccess($wali, $data['student_id']);

        $student = Student::findOrFail($data['student_id']);
        $visit = $this->visits->submit($data, $student->dormitory_id);

        return redirect()->route('portal.dashboard', ['token' => $token])
            ->with('success', 'Permohonan penjengukan telah dikirim dan menunggu persetujuan.');
    }

    /**
     * Submit izin sakit / rawat jalan.
     */
    public function submitHealth(Request $request, string $token)
    {
        $wali = $this->resolveWaliByToken($token);
        if (! $wali) {
            return view('portal.token-expired');
        }
        Auth::loginUsingId($wali->user_id);

        $data = $request->validate([
            'student_id' => 'required|uuid',
            'permit_type' => 'required|in:sakit,rawat_jalan,kontrol,istirahat',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'required|string|max:500',
            'doctor_name' => 'nullable|string|max:255',
            'medical_facility' => 'nullable|string|max:255',
        ]);

        $this->authorizeWaliAccess($wali, $data['student_id']);

        $student = Student::findOrFail($data['student_id']);
        $data['dormitory_id'] = $student->dormitory_id;

        $permit = $this->health->submit($data);

        return redirect()->route('portal.dashboard', ['token' => $token])
            ->with('success', 'Izin sakit telah dikirim dan menunggu persetujuan.');
    }

    /**
     * Resolve wali from URL token, returning null if invalid/expired.
     * Tries both plaintext and SHA-256 hashed comparison to cover
     * different storage conventions (some systems store plaintext,
     * others store hashes).
     */
    private function resolveWaliByToken(string $token): ?WaliSantri
    {
        if (empty($token)) {
            return null;
        }

        $hash = hash('sha256', $token);

        $wali = WaliSantri::where(function ($q) use ($token, $hash) {
            $q->where('access_token', $token)
                ->orWhere('access_token', $hash);
        })
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->where('access_expires_at', '>', now())
            ->first();

        return $wali;
    }

    /**
     * Verify the authenticated wali has access to the given student.
     */
    private function authorizeWaliAccess(WaliSantri $wali, string $studentId): void
    {
        $allowed = WaliSantri::where('user_id', $wali->user_id)
            ->where('student_id', $studentId)
            ->active()
            ->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki akses untuk siswa ini.');
    }
}
