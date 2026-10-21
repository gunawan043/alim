<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippMember;
use App\Models\Osipp\OsippQism;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OsippMemberController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $this->workspace->ensurePeriod($context);

        $members = OsippMember::query()
            ->with(['student:id,name,nis,nisn,gender', 'qism:id,name'])
            ->where('period_id', $period->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('student', fn ($student) => $student->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('nis', 'like', '%'.$request->search.'%')
                    ->orWhere('nisn', 'like', '%'.$request->search.'%'));
            })
            ->orderBy('sort_order')
            ->orderBy('position')
            ->paginate(20)
            ->withQueryString();

        $positions = OsippMember::query()
            ->where('period_id', $period->id)
            ->select('position')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');

        $qisms = OsippQism::query()
            ->where('period_id', $period->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $membersJson = $members->getCollection()->map(fn (OsippMember $member) => [
            'id' => $member->id,
            'student_id' => $member->student_id,
            'student_name' => $member->student?->name,
            'position' => $member->position,
            'position_code' => $member->position_code,
            'division' => $member->division,
            'member_role' => $member->member_role,
            'qism_id' => $member->qism_id,
            'qism_name' => $member->qism?->name,
            'start_date' => $member->start_date?->format('Y-m-d'),
            'end_date' => $member->end_date?->format('Y-m-d'),
            'status' => $member->status,
            'sort_order' => $member->sort_order,
            'notes' => $member->notes,
            'update_url' => route('osipp.anggota.update', ['userId' => $context['userId'], 'member' => $member->id]),
        ])->values();

        return view('osipp.anggota.index', array_merge($context, compact('period', 'members', 'positions', 'membersJson', 'qisms')));
    }

    public function searchStudents(Request $request)
    {
        $context = $this->context($request);
        $search = trim((string) $request->input('q', ''));

        $students = $this->workspace->studentQuery($context['schoolId'], $search)
            ->limit(25)
            ->get(['id', 'name', 'nis', 'nisn', 'gender']);

        return response()->json([
            'results' => $students->map(fn ($student) => [
                'id' => $student->id,
                'text' => $student->name.' · '.($student->nis ?: $student->nisn ?: 'Tanpa NIS'),
                'name' => $student->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);

        $data = $this->validated($request, $period->id);
        $data = $this->normalizeStructure($data);
        $this->assertStudentInSchool($data['student_id'], $context['schoolId']);

        $member = OsippMember::create($data + [
            'period_id' => $period->id,
            'created_by' => $context['user']->id,
        ]);
        $this->syncQismLeadership($member);

        return back()->with('success', 'Anggota kepengurusan OSIPP ditambahkan.');
    }

    public function update(Request $request, string $userId, OsippMember $member)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $member->period_id === (string) $period->id, 404);

        $data = $this->validated($request, $period->id, $member);
        $data = $this->normalizeStructure($data);
        $this->assertStudentInSchool($data['student_id'], $context['schoolId']);

        $oldQismId = $member->qism_id;
        $wasLeader = $member->member_role === OsippMember::ROLE_KETUA_QISM;
        $member->update($data);

        if ($wasLeader && (string) $oldQismId !== (string) $member->qism_id) {
            OsippQism::query()
                ->whereKey($oldQismId)
                ->where('leader_member_id', $member->id)
                ->update(['leader_member_id' => null]);
        }

        $this->syncQismLeadership($member);

        return back()->with('success', 'Data anggota OSIPP diperbarui.');
    }

    public function destroy(Request $request, string $userId, OsippMember $member)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $member->period_id === (string) $period->id, 404);

        OsippQism::query()
            ->where('leader_member_id', $member->id)
            ->update(['leader_member_id' => null]);

        $member->delete();

        return back()->with('success', 'Anggota OSIPP dihapus dari kepengurusan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $periodId, ?OsippMember $member = null): array
    {
        return $request->validate([
            'student_id' => [
                'required',
                'uuid',
                Rule::exists('students', 'id'),
                Rule::unique('osipp_members', 'student_id')
                    ->where(fn ($q) => $q->where('period_id', $periodId))
                    ->ignore($member?->id),
            ],
            'position' => ['required', 'string', 'max:100'],
            'position_code' => ['nullable', 'string', 'max:50'],
            'division' => ['nullable', 'string', 'max:100'],
            'member_role' => ['required', Rule::in(array_keys(OsippMember::ROLE_OPTIONS))],
            'qism_id' => [
                'nullable',
                'uuid',
                Rule::exists('osipp_qisms', 'id')->where(fn ($q) => $q->where('period_id', $periodId)),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(OsippMember::STATUS_OPTIONS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeStructure(array $data): array
    {
        if (($data['member_role'] ?? null) === OsippMember::ROLE_PENGURUS_INTI) {
            $data['qism_id'] = null;
        }

        if (($data['member_role'] ?? null) === OsippMember::ROLE_KETUA_QISM && empty($data['qism_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qism_id' => 'Ketua Qism wajib memilih Qism.',
            ]);
        }

        return $data;
    }

    private function syncQismLeadership(OsippMember $member): void
    {
        if (! $member->qism_id) {
            return;
        }

        if ($member->member_role === OsippMember::ROLE_KETUA_QISM) {
            OsippQism::query()
                ->whereKey($member->qism_id)
                ->update(['leader_member_id' => $member->id]);

            return;
        }

        OsippQism::query()
            ->whereKey($member->qism_id)
            ->where('leader_member_id', $member->id)
            ->update(['leader_member_id' => null]);
    }

    private function assertStudentInSchool(string $studentId, string $schoolId): void
    {
        abort_unless(Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereKey($studentId)
            ->exists(), 422, 'Santri harus aktif dan berasal dari satuan pendidikan ini.');
    }
}
