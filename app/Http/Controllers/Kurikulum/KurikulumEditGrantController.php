<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\KurikulumEditGrant;
use App\Models\Subject;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * Permintaan Akses Edit dokumen kurikulum (kebijakan "keroyokan terkendali").
 *
 * - Guru serumpun tanpa penugasan dapat MELIHAT & MENYALIN dokumen.
 * - Untuk mengedit, ajukan permintaan → disetujui pengampu mapel / tim kurikulum.
 * - Pemegang izin dapat mengelola TP/ATP/PROTA/PROSEM/RPM mapel tersebut
 *   pada tahun ajaran yang diminta (via KurikulumAccess::canManageSubject).
 */
class KurikulumEditGrantController extends Controller
{
    public function __construct(private readonly KurikulumAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        // Mapel yang bisa diminta: mapel yang boleh dilihat (serumpun) & belum bisa dikelola.
        $viewableIds = $this->access->viewableSubjectIds($user, $schoolId);

        $requestableSubjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when($viewableIds !== null, fn ($q) => $q->whereIn('id', $viewableIds ?: ['-']))
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->filter(fn ($subject) => $activeAy
                && $this->access->canRequestEditGrant($user, $subject->id, $schoolId, $activeAy->id))
            ->values();

        $myRequests = KurikulumEditGrant::with(['subject:id,name,code', 'academicYear:id,name', 'decider:id,name'])
            ->where('requested_by', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $pendingForMe = KurikulumEditGrant::with(['subject:id,name,code', 'academicYear:id,name', 'requester:id,name'])
            ->pending()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('requested_by', '!=', $user->id)
            ->orderBy('created_at')
            ->get()
            ->filter(fn ($grant) => $this->access->canApproveEditGrant($user, $grant->subject_id, $grant->academic_year_id, $grant->school_id))
            ->values();

        $activeGrants = KurikulumEditGrant::with(['subject:id,name,code', 'academicYear:id,name', 'requester:id,name'])
            ->approved()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('requested_by', '!=', $user->id)
            ->orderByDesc('decided_at')
            ->get()
            ->filter(fn ($grant) => $this->access->canApproveEditGrant($user, $grant->subject_id, $grant->academic_year_id, $grant->school_id))
            ->values();

        return view('kurikulum.akses-edit.index', compact(
            'userId',
            'academicYears',
            'activeAy',
            'requestableSubjects',
            'myRequests',
            'pendingForMe',
            'activeGrants'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        if (! $this->access->canViewSubjectDocs($user, $data['subject_id'], $schoolId, $data['academic_year_id'])) {
            abort(403, 'Mapel ini di luar kewenangan Anda.');
        }

        if ($this->access->canManageSubject($user, $data['subject_id'], $data['academic_year_id'], $schoolId)) {
            return back()->with('error', 'Anda sudah memiliki akses edit untuk mapel ini.');
        }

        $exists = KurikulumEditGrant::query()
            ->where('requested_by', $user->id)
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->whereIn('status', [KurikulumEditGrant::STATUS_PENDING, KurikulumEditGrant::STATUS_APPROVED])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Permintaan untuk mapel ini masih berjalan atau sudah disetujui.');
        }

        KurikulumEditGrant::create([
            'school_id' => $schoolId,
            'subject_id' => $data['subject_id'],
            'academic_year_id' => $data['academic_year_id'],
            'requested_by' => $user->id,
            'status' => KurikulumEditGrant::STATUS_PENDING,
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('success', 'Permintaan akses edit dikirim — menunggu persetujuan pengampu mapel / tim kurikulum.');
    }

    public function approve(Request $request, string $userId, string $id)
    {
        $grant = KurikulumEditGrant::findOrFail($id);
        $this->authorizeDecision($request, $grant);

        if ($grant->status !== KurikulumEditGrant::STATUS_PENDING) {
            return back()->with('error', 'Permintaan ini sudah diputuskan.');
        }

        $grant->update([
            'status' => KurikulumEditGrant::STATUS_APPROVED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $request->input('decision_note'),
        ]);

        return back()->with('success', 'Akses edit diberikan kepada '.($grant->requester?->name ?? 'pemohon').'.');
    }

    public function reject(Request $request, string $userId, string $id)
    {
        $grant = KurikulumEditGrant::findOrFail($id);
        $this->authorizeDecision($request, $grant);

        if ($grant->status !== KurikulumEditGrant::STATUS_PENDING) {
            return back()->with('error', 'Permintaan ini sudah diputuskan.');
        }

        $grant->update([
            'status' => KurikulumEditGrant::STATUS_REJECTED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $request->input('decision_note'),
        ]);

        return back()->with('success', 'Permintaan akses edit ditolak.');
    }

    public function revoke(Request $request, string $userId, string $id)
    {
        $grant = KurikulumEditGrant::findOrFail($id);
        $this->authorizeDecision($request, $grant);

        if ($grant->status !== KurikulumEditGrant::STATUS_APPROVED) {
            return back()->with('error', 'Hanya akses yang masih aktif yang dapat dicabut.');
        }

        $grant->update([
            'status' => KurikulumEditGrant::STATUS_REVOKED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return back()->with('success', 'Akses edit dicabut kembali.');
    }

    private function authorizeDecision(Request $request, KurikulumEditGrant $grant): void
    {
        if (! $this->access->canApproveEditGrant($request->user(), $grant->subject_id, $grant->academic_year_id, $grant->school_id)) {
            abort(403, 'Hanya pengampu mapel atau tim kurikulum yang dapat memutuskan permintaan ini.');
        }
    }
}
