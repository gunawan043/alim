<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Uks\UksPatient;
use App\Services\NotificationUniversalService;
use Illuminate\Http\Request;

/**
 * Notifikasi Wali Santri untuk pasien UKS.
 *
 * Memakai NotificationUniversalService::notifyGuardiansForStudents() yang sudah
 * dipakai modul asrama/nilai, sehingga notifikasi masuk ke kanal yang sama.
 */
class GuardianNotificationController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $search = trim((string) $request->input('search'));

        $patients = UksPatient::query()
            ->with(['student:id,name,nis,nisn,guardian_name,mobile_phone,phone'])
            ->where(function ($q) {
                $q->whereDate('queue_date', now()->toDateString())
                    ->orWhereIn('status', [
                        UksPatient::STATUS_TREATED,
                        UksPatient::STATUS_OBSERVATION,
                        UksPatient::STATUS_INPATIENT,
                        UksPatient::STATUS_RESTING_UKS,
                    ]);
            })
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('student', function ($s) use ($search) {
                    $s->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('queue_date')
            ->limit(100)
            ->get();

        return view('uks.guardian-notifications.index', [
            'userId' => $userId,
            'patients' => $patients,
            'search' => $search,
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'exists:uks_patients,id',
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:500',
        ]);

        $patients = UksPatient::whereIn('id', $validated['patient_ids'])
            ->whereNotNull('student_id')
            ->get(['id', 'student_id']);

        if ($patients->isEmpty()) {
            return back()->with('error', 'Tidak ada pasien dengan data santri yang bisa dinotifikasi.');
        }

        $sent = app(NotificationUniversalService::class)->notifyGuardiansForStudents(
            $patients->pluck('student_id')->unique()->values()->all(),
            [
                'module' => 'uks',
                'reference_type' => 'uks_patient',
                'reference_id' => $patients->first()->id,
                'type' => 'info',
                'action' => 'uks_guardian_notification',
                'title' => $validated['title'],
                'message' => $validated['message'],
                'priority' => 'medium',
            ]
        );

        return redirect()
            ->route('user.uks.guardian-notifications.index', ['userId' => $userId])
            ->with('success', $sent > 0
                ? "Notifikasi terkirim ke {$sent} wali santri."
                : 'Tidak ada wali santri yang memiliki akun untuk menerima notifikasi.');
    }
}
