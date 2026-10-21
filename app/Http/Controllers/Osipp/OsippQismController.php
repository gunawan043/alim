<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippMember;
use App\Models\Osipp\OsippQism;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OsippQismController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $this->workspace->ensurePeriod($context);

        $qisms = OsippQism::query()
            ->with([
                'leader.student:id,name,nis,nisn',
                'members.student:id,name,nis,nisn',
            ])
            ->where('period_id', $period->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $members = OsippMember::query()
            ->with('student:id,name,nis,nisn')
            ->where('period_id', $period->id)
            ->where('status', OsippMember::STATUS_ACTIVE)
            ->whereIn('member_role', [OsippMember::ROLE_ANGGOTA, OsippMember::ROLE_KETUA_QISM])
            ->orderBy('sort_order')
            ->orderBy('position')
            ->get();

        $stats = $this->workspace->statsForPeriod($period);

        $qismsJson = $qisms->map(fn (OsippQism $qism) => [
            'id' => $qism->id,
            'name' => $qism->name,
            'code' => $qism->code,
            'description' => $qism->description,
            'leader_member_id' => $qism->leader_member_id,
            'sort_order' => $qism->sort_order,
            'is_active' => $qism->is_active,
            'member_ids' => $qism->members->pluck('id')->values(),
            'update_url' => route('osipp.qism.update', ['userId' => $context['userId'], 'qism' => $qism->id]),
        ])->values();

        return view('osipp.qism.index', array_merge($context, compact('period', 'qisms', 'members', 'qismsJson', 'stats')));
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);

        $data = $this->validated($request, $period->id);

        DB::transaction(function () use ($data, $request, $period) {
            $qism = OsippQism::create([
                'period_id' => $period->id,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'leader_member_id' => $data['leader_member_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncMembers($qism, $request, $period->id);
        });

        return back()->with('success', 'Qism OSIPP berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, OsippQism $qism)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $qism->period_id === (string) $period->id, 404);

        $data = $this->validated($request, $period->id, $qism);

        DB::transaction(function () use ($qism, $data, $request, $period) {
            $qism->update([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'leader_member_id' => $data['leader_member_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncMembers($qism, $request, $period->id);
        });

        return back()->with('success', 'Qism OSIPP berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, OsippQism $qism)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $qism->period_id === (string) $period->id, 404);

        DB::transaction(function () use ($qism) {
            OsippMember::query()
                ->where('qism_id', $qism->id)
                ->get()
                ->each(function (OsippMember $member) {
                    $member->update([
                        'qism_id' => null,
                        'member_role' => $member->member_role === OsippMember::ROLE_KETUA_QISM
                            ? OsippMember::ROLE_ANGGOTA
                            : $member->member_role,
                    ]);
                });

            $qism->delete();
        });

        return back()->with('success', 'Qism OSIPP berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $periodId, ?OsippQism $qism = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('osipp_qisms', 'name')
                    ->where(fn ($q) => $q->where('period_id', $periodId))
                    ->ignore($qism?->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('osipp_qisms', 'code')
                    ->where(fn ($q) => $q->where('period_id', $periodId))
                    ->ignore($qism?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'leader_member_id' => [
                'nullable',
                'uuid',
                Rule::exists('osipp_members', 'id')->where(fn ($q) => $q->where('period_id', $periodId)),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['uuid'],
        ]);
    }

    private function syncMembers(OsippQism $qism, Request $request, string $periodId): void
    {
        $leaderId = $qism->leader_member_id;
        $selectedIds = collect($request->input('member_ids', []))
            ->filter()
            ->push($leaderId)
            ->filter()
            ->unique()
            ->values();

        $validMembers = OsippMember::query()
            ->where('period_id', $periodId)
            ->whereIn('member_role', [OsippMember::ROLE_ANGGOTA, OsippMember::ROLE_KETUA_QISM])
            ->whereIn('id', $selectedIds)
            ->get();

        if ($selectedIds->isNotEmpty() && $validMembers->count() !== $selectedIds->count()) {
            throw ValidationException::withMessages([
                'member_ids' => 'Semua anggota Qism harus berasal dari periode ini.',
            ]);
        }

        // Jika anggota dipindahkan dari Qism lain, lepaskan jabatan ketua lamanya.
        OsippQism::query()
            ->where('period_id', $periodId)
            ->where('id', '!=', $qism->id)
            ->whereIn('leader_member_id', $selectedIds)
            ->update(['leader_member_id' => null]);

        // Lepas anggota yang tidak lagi dipilih, kecuali ketua.
        OsippMember::query()
            ->where('qism_id', $qism->id)
            ->when($leaderId, fn ($query) => $query->where('id', '!=', $leaderId))
            ->whereNotIn('id', $selectedIds)
            ->get()
            ->each(function (OsippMember $member) {
                $member->update([
                    'qism_id' => null,
                    'member_role' => $member->member_role === OsippMember::ROLE_KETUA_QISM
                        ? OsippMember::ROLE_ANGGOTA
                        : $member->member_role,
                ]);
            });

        foreach ($validMembers as $member) {
            $isLeader = (string) $member->id === (string) $leaderId;
            $member->update([
                'qism_id' => $qism->id,
                'member_role' => $isLeader
                    ? OsippMember::ROLE_KETUA_QISM
                    : OsippMember::ROLE_ANGGOTA,
            ]);
        }

        // Sinkronkan ketua Qism agar hanya satu pemegang jabatan.
        if ($leaderId) {
            OsippMember::query()
                ->where('qism_id', $qism->id)
                ->where('id', '!=', $leaderId)
                ->where('member_role', OsippMember::ROLE_KETUA_QISM)
                ->update(['member_role' => OsippMember::ROLE_ANGGOTA]);
        }
    }
}
