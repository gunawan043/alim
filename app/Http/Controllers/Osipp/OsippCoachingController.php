<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippCoaching;
use App\Models\Osipp\OsippCoachingAttendance;
use App\Models\Osipp\OsippMember;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OsippCoachingController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $this->workspace->ensurePeriod($context);

        $coachings = OsippCoaching::query()
            ->with([
                'mentor:id,name',
                'member.student:id,name,nis,nisn',
                'attendances.member.student:id,name,nis,nisn',
            ])
            ->where('period_id', $period->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('topic', 'like', '%'.$request->search.'%')
                        ->orWhere('summary', 'like', '%'.$request->search.'%')
                        ->orWhere('follow_up', 'like', '%'.$request->search.'%');
                });
            })
            ->orderByDesc('coaching_date')
            ->paginate(20)
            ->withQueryString();

        $members = $this->workspace->memberOptions($period);
        $statusOptions = OsippCoaching::STATUS_OPTIONS;
        $typeOptions = OsippCoaching::TYPE_OPTIONS;

        $coachingsJson = $coachings->getCollection()->map(fn (OsippCoaching $coaching) => [
            'id' => $coaching->id,
            'coaching_type' => $coaching->coaching_type,
            'coaching_date' => $coaching->coaching_date?->format('Y-m-d'),
            'topic' => $coaching->topic,
            'member_id' => $coaching->member_id,
            'mentor_user_id' => $coaching->mentor_user_id,
            'location' => $coaching->location,
            'summary' => $coaching->summary,
            'follow_up' => $coaching->follow_up,
            'status' => $coaching->status,
            'member_ids' => $coaching->attendances->pluck('member_id')->values(),
            'update_url' => route('osipp.pembinaan.update', ['userId' => $context['userId'], 'coaching' => $coaching->id]),
        ])->values();

        return view('osipp.pembinaan.index', array_merge($context, compact(
            'period', 'coachings', 'members', 'statusOptions', 'typeOptions', 'coachingsJson'
        )));
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);

        $data = $this->validated($request);
        unset($data['member_ids']);

        $coaching = OsippCoaching::create($data + [
            'period_id' => $period->id,
            'mentor_user_id' => $data['mentor_user_id'] ?? $context['user']->id,
            'created_by' => $context['user']->id,
            'updated_by' => $context['user']->id,
        ]);

        $this->syncAttendances($coaching, $request, $period);

        return back()->with('success', 'Catatan pembinaan OSIPP disimpan.');
    }

    public function update(Request $request, string $userId, OsippCoaching $coaching)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $coaching->period_id === (string) $period->id, 404);

        $data = $this->validated($request);
        unset($data['member_ids']);
        $coaching->update($data + ['updated_by' => $context['user']->id]);

        $this->syncAttendances($coaching, $request, $period);

        return back()->with('success', 'Catatan pembinaan OSIPP diperbarui.');
    }

    public function destroy(Request $request, string $userId, OsippCoaching $coaching)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $coaching->period_id === (string) $period->id, 404);

        $coaching->delete();

        return back()->with('success', 'Catatan pembinaan OSIPP dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'coaching_type' => ['required', Rule::in(array_keys(OsippCoaching::TYPE_OPTIONS))],
            'coaching_date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:180'],
            'mentor_user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'location' => ['nullable', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'follow_up' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(OsippCoaching::STATUS_OPTIONS))],
            'member_id' => ['nullable', 'uuid', Rule::exists('osipp_members', 'id')],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['uuid'],
        ]);

        if ($data['coaching_type'] === OsippCoaching::TYPE_INDIVIDUAL && empty($data['member_id'])) {
            abort(422, 'Pembinaan individu harus memilih satu anggota.');
        }

        return $data;
    }

    private function syncAttendances(OsippCoaching $coaching, Request $request, $period): void
    {
        $ids = collect($request->input('member_ids', []))
            ->push($request->input('member_id'))
            ->filter()
            ->unique()
            ->values();

        $validIds = OsippMember::query()
            ->where('period_id', $period->id)
            ->whereIn('id', $ids)
            ->pluck('id');

        abort_unless($validIds->count() === $ids->count(), 422, 'Peserta pembinaan harus anggota periode ini.');

        $coaching->attendances()->whereNotIn('member_id', $validIds)->delete();

        foreach ($validIds as $memberId) {
            OsippCoachingAttendance::updateOrCreate(
                ['coaching_id' => $coaching->id, 'member_id' => $memberId],
                ['status' => OsippCoachingAttendance::STATUS_PRESENT]
            );
        }
    }
}
