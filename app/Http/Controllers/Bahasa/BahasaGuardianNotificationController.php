<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\LanguageViolation;
use App\Services\BahasaAccess;
use App\Services\NotificationUniversalService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Notifikasi Wali Santri untuk pelanggaran bahasa.
 *
 * Merangkum pelanggaran per santri pada rentang tanggal, lalu mengirim
 * pemberitahuan ke wali lewat kanal notifikasi yang sama dengan modul lain.
 */
class BahasaGuardianNotificationController extends Controller
{
    public function __construct(private readonly BahasaAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka modul Bahasa.');

        $schoolId = $request->attributes->get('schoolContextId');
        $from = Carbon::parse($request->input('from') ?: now()->subDays(29)->toDateString())->toDateString();
        $to = Carbon::parse($request->input('to') ?: now()->toDateString())->toDateString();
        $search = trim((string) $request->input('search'));

        $violations = LanguageViolation::query()
            ->with(['student:id,name,nis,nisn,guardian_name,mobile_phone,phone'])
            ->whereBetween('violation_date', [$from, $to])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('student', function ($s) use ($search) {
                    $s->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('violation_date')
            ->get();

        $grouped = $violations
            ->filter(fn ($v) => $v->student !== null)
            ->groupBy('student_id')
            ->map(function ($items) {
                $student = $items->first()->student;

                return [
                    'student' => $student,
                    'total' => $items->count(),
                    'points' => (int) $items->sum('points'),
                    'last' => $items->max('violation_date'),
                    'types' => $items->pluck('violation_type')->filter()->unique()->values()->all(),
                    'has_contact' => filled($student->mobile_phone) || filled($student->phone),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('bahasa.guardian-notifications.index', [
            'userId' => $userId,
            'grouped' => $grouped,
            'from' => $from,
            'to' => $to,
            'search' => $search,
            'totalViolations' => $violations->count(),
        ]);
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka modul Bahasa.');

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:500',
        ]);

        $sent = app(NotificationUniversalService::class)->notifyGuardiansForStudents(
            $validated['student_ids'],
            [
                'module' => 'bahasa',
                'reference_type' => 'language_violation',
                'type' => 'warning',
                'action' => 'bahasa_guardian_notification',
                'title' => $validated['title'],
                'message' => $validated['message'],
                'priority' => 'medium',
            ]
        );

        return redirect()
            ->route('user.bahasa.guardian-notifications.index', ['userId' => $userId])
            ->with('success', $sent > 0
                ? "Notifikasi terkirim ke {$sent} wali santri."
                : 'Tidak ada wali santri yang memiliki akun untuk menerima notifikasi.');
    }
}
