<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippActivity;
use App\Models\Osipp\OsippProgram;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OsippActivityController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $this->workspace->ensurePeriod($context);

        $activities = OsippActivity::query()
            ->with([
                'program:id,name',
                'responsible.student:id,name,nis,nisn',
                'verifier:id,name',
            ])
            ->where('period_id', $period->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('description', 'like', '%'.$request->search.'%')
                        ->orWhere('location', 'like', '%'.$request->search.'%');
                });
            })
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 0 ELSE 1 END")
            ->orderByDesc('activity_date')
            ->paginate(20)
            ->withQueryString();

        $programs = OsippProgram::query()
            ->where('period_id', $period->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $members = $this->workspace->memberOptions($period);
        $statusOptions = OsippActivity::STATUS_OPTIONS;

        $summary = [
            'total' => OsippActivity::query()->where('period_id', $period->id)->count(),
            'submitted' => OsippActivity::query()->where('period_id', $period->id)->where('status', OsippActivity::STATUS_SUBMITTED)->count(),
            'verified' => OsippActivity::query()->where('period_id', $period->id)->where('status', OsippActivity::STATUS_VERIFIED)->count(),
            'completed' => OsippActivity::query()->where('period_id', $period->id)->where('status', OsippActivity::STATUS_COMPLETED)->count(),
        ];

        $activitiesJson = $activities->getCollection()->map(fn (OsippActivity $activity) => [
            'id' => $activity->id,
            'title' => $activity->title,
            'program_id' => $activity->program_id,
            'category' => $activity->category,
            'activity_date' => $activity->activity_date?->format('Y-m-d'),
            'start_time' => $activity->start_time ? substr((string) $activity->start_time, 0, 5) : '',
            'end_time' => $activity->end_time ? substr((string) $activity->end_time, 0, 5) : '',
            'location' => $activity->location,
            'participant_count' => $activity->participant_count,
            'responsible_member_id' => $activity->responsible_member_id,
            'description' => $activity->description,
            'status' => $activity->status,
            'verification_notes' => $activity->verification_notes,
            'documentation_url' => $activity->documentation_url,
            'update_url' => route('osipp.kegiatan.update', ['userId' => $context['userId'], 'activity' => $activity->id]),
        ])->values();

        return view('osipp.kegiatan.index', array_merge($context, compact(
            'period', 'activities', 'programs', 'members', 'statusOptions', 'activitiesJson', 'summary'
        )));
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);

        $data = $this->validated($request, $period->id);

        OsippActivity::create($data + [
            'period_id' => $period->id,
            'status' => OsippActivity::STATUS_DRAFT,
            'created_by' => $context['user']->id,
            'updated_by' => $context['user']->id,
        ]);

        return back()->with('success', 'Kegiatan OSIPP ditambahkan sebagai draft.');
    }

    public function update(Request $request, string $userId, OsippActivity $activity)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($activity->period);
        abort_unless($activity->isEditable(), 422, 'Kegiatan yang sudah diverifikasi tidak dapat diedit.');

        $data = $this->validated($request, $activity->period_id);
        $activity->update($data + ['updated_by' => $context['user']->id]);

        return back()->with('success', 'Kegiatan OSIPP diperbarui.');
    }

    public function submit(Request $request, string $userId, OsippActivity $activity)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($activity->period);

        $this->workflow->transitionActivity($activity, OsippActivity::STATUS_SUBMITTED, $request->user());

        return back()->with('success', 'Kegiatan diajukan untuk verifikasi.');
    }

    public function verify(Request $request, string $userId, OsippActivity $activity)
    {
        $context = $this->context($request);
        $this->ensureVerify($context);
        $this->authorizePeriod($activity->period);

        $data = $request->validate(['verification_notes' => ['nullable', 'string', 'max:3000']]);
        $this->workflow->transitionActivity($activity, OsippActivity::STATUS_VERIFIED, $request->user(), $data['verification_notes'] ?? null);

        return back()->with('success', 'Kegiatan OSIPP berhasil diverifikasi.');
    }

    public function revision(Request $request, string $userId, OsippActivity $activity)
    {
        $context = $this->context($request);
        $this->ensureVerify($context);
        $this->authorizePeriod($activity->period);

        $data = $request->validate(['verification_notes' => ['required', 'string', 'max:3000']]);
        $this->workflow->transitionActivity($activity, OsippActivity::STATUS_REVISION, $request->user(), $data['verification_notes']);

        return back()->with('success', 'Kegiatan dikembalikan untuk revisi.');
    }

    public function reject(Request $request, string $userId, OsippActivity $activity)
    {
        $context = $this->context($request);
        $this->ensureVerify($context);
        $this->authorizePeriod($activity->period);

        $data = $request->validate(['verification_notes' => ['required', 'string', 'max:3000']]);
        $this->workflow->transitionActivity($activity, OsippActivity::STATUS_REJECTED, $request->user(), $data['verification_notes']);

        return back()->with('success', 'Kegiatan OSIPP ditolak.');
    }

    public function complete(Request $request, string $userId, OsippActivity $activity)
    {
        $context = $this->context($request);
        $this->ensureVerify($context);
        $this->authorizePeriod($activity->period);

        $data = $request->validate(['verification_notes' => ['nullable', 'string', 'max:3000']]);
        $this->workflow->transitionActivity($activity, OsippActivity::STATUS_COMPLETED, $request->user(), $data['verification_notes'] ?? null);

        return back()->with('success', 'Kegiatan OSIPP ditandai selesai.');
    }

    public function destroy(Request $request, string $userId, OsippActivity $activity)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($activity->period);
        abort_unless($activity->isEditable(), 422, 'Kegiatan yang sudah diverifikasi tidak dapat dihapus.');

        $activity->delete();

        return back()->with('success', 'Kegiatan OSIPP dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $periodId): array
    {
        $data = $request->validate([
            'program_id' => [
                'nullable',
                'uuid',
                Rule::exists('osipp_programs', 'id')->where(fn ($q) => $q->where('period_id', $periodId)),
            ],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'activity_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'location' => ['nullable', 'string', 'max:180'],
            'participant_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'responsible_member_id' => [
                'nullable',
                'uuid',
                Rule::exists('osipp_members', 'id')->where(fn ($q) => $q->where('period_id', $periodId)),
            ],
            'documentation_url' => ['nullable', 'url', 'max:500'],
        ]);

        if (empty($data['responsible_member_id']) && $request->filled('responsible_member_id')) {
            abort(422, 'Penanggung jawab harus anggota periode ini.');
        }

        return $data;
    }
}
