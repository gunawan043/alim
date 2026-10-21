<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippMember;
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
            ->with('student:id,name,nis,nisn,gender')
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

        $membersJson = $members->getCollection()->map(fn (OsippMember $member) => [
            'id' => $member->id,
            'student_id' => $member->student_id,
            'student_name' => $member->student?->name,
            'position' => $member->position,
            'position_code' => $member->position_code,
            'division' => $member->division,
            'member_role' => $member->member_role,
            'start_date' => $member->start_date?->format('Y-m-d'),
            'end_date' => $member->end_date?->format('Y-m-d'),
            'status' => $member->status,
            'sort_order' => $member->sort_order,
            'notes' => $member->notes,
            'update_url' => route('osipp.anggota.update', ['userId' => $context['userId'], 'member' => $member->id]),
        ])->values();

        return view('osipp.anggota.index', array_merge($context, compact('period', 'members', 'positions', 'membersJson')));
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
        $this->assertStudentInSchool($data['student_id'], $context['schoolId']);

        OsippMember::create($data + [
            'period_id' => $period->id,
            'created_by' => $context['user']->id,
        ]);

        return back()->with('success', 'Anggota kepengurusan OSIPP ditambahkan.');
    }

    public function update(Request $request, string $userId, OsippMember $member)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $member->period_id === (string) $period->id, 404);

        $data = $this->validated($request, $period->id, $member);
        $this->assertStudentInSchool($data['student_id'], $context['schoolId']);

        $member->update($data);

        return back()->with('success', 'Data anggota OSIPP diperbarui.');
    }

    public function destroy(Request $request, string $userId, OsippMember $member)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $member->period_id === (string) $period->id, 404);

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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(OsippMember::STATUS_OPTIONS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
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
