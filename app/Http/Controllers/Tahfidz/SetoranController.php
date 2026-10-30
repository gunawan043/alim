<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzSurahMaster;
use App\Services\QuranPositionService;
use App\Services\TahfidzAccess;
use App\Services\TahfidzProgressService;
use Illuminate\Http\Request;

class SetoranController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzProgressService $progress,
    ) {}

    public function index(Request $request, string $userId)
    {
        $groups = $this->accessibleGroups($request);
        $groupIds = $groups->pluck('id');
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        $query = TahfidzSetoran::query()
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($request->filled('group_id'), fn ($q) => $q->where('tahfidz_group_id', $request->group_id))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('setoran_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('setoran_date', '<=', $request->date_to))
            ->when($request->filled('verification'), fn ($q) => $q->where('verification_status', $request->verification));

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('verification_status', 'pending')->count(),
            'verified' => (clone $query)->where('verification_status', 'verified')->count(),
            'today' => (clone $query)->whereDate('setoran_date', now()->toDateString())->count(),
        ];

        $setorans = $query->with(['student:id,name,nis', 'group:id,name,school_id', 'teacher:id,name', 'verifier:id,name'])
            ->orderByDesc('setoran_date')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $memberRows = TahfidzGroupMember::with('student:id,name,nis')
            ->whereIn('tahfidz_group_id', $groupIds)
            ->where('status', 'aktif')
            ->get();
        $memberStudentIds = $memberRows->pluck('student_id')->unique();
        $lastPositions = TahfidzSetoran::whereIn('student_id', $memberStudentIds)
            ->orderByDesc('setoran_date')
            ->orderByDesc('created_at')
            ->get()
            ->unique('student_id')
            ->mapWithKeys(fn ($last) => [$last->student_id => [
                'juz' => $last->juz,
                'surah_start_name' => $last->surah_end_name ?: $last->surah_start_name,
                'ayat_start' => $last->ayat_end ? ((int) $last->ayat_end + 1) : $last->ayat_start,
                'halaman_start' => $last->halaman_end ? ((float) $last->halaman_end + 1) : $last->halaman_start,
            ]]);

        $membersByGroup = $memberRows
            ->groupBy('tahfidz_group_id')
            ->map(fn ($members) => $members->map(fn ($member) => [
                'id' => $member->student?->id,
                'name' => $member->student?->name,
                'nis' => $member->student?->nis,
                'last' => $lastPositions[$member->student_id] ?? null,
            ])->filter(fn ($student) => $student['id'])->values());

        $surahs = TahfidzSurahMaster::orderBy('number')->get(['number', 'name_latin', 'name_arabic']);
        $canVerify = $this->access->isSuperAdmin($request->user())
            || $this->access->isDepartment($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request));

        return view('tahfidz.setoran.index', compact('userId', 'setorans', 'groups', 'academicYears', 'canVerify', 'stats', 'membersByGroup', 'surahs'));
    }

    public function create(Request $request, string $userId)
    {
        $groups = $this->accessibleGroups($request);
        if ($groups->isEmpty()) {
            return redirect()
                ->route('user.tahfidz.setoran.index', ['userId' => $userId])
                ->with('error', 'Belum ada halaqah yang dapat Anda input. Hubungi koordinator Tahfidz sekolah.');
        }

        $groupId = $request->input('group_id', $groups->first()->id);
        $group = $groups->firstWhere('id', $groupId) ?? $groups->first();
        $students = $this->members($group);
        $surahs = TahfidzSurahMaster::orderBy('number')->get(['number', 'name_latin', 'name_arabic']);

        return view('tahfidz.setoran.form', compact('userId', 'groups', 'group', 'students', 'surahs'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $this->validateSetoran($request);
        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        $this->authorizeInput($request, $group);
        $this->assertMember($group, $validated['student_id']);

        $data = $this->payload($validated, $group, $request);
        $autoVerify = (bool) config('tahfidz.auto_verify_setoran', true);
        $data['verification_status'] = $autoVerify ? 'verified' : 'pending';

        if ($autoVerify) {
            $data['verified_by'] = $request->user()?->id;
            $data['verified_at'] = now();
            $data['verification_notes'] = 'auto-verified';
        }

        $setoran = TahfidzSetoran::create($data);

        if ($autoVerify) {
            $this->progress->recalculateForSetoran($setoran);
        }

        $message = $autoVerify
            ? 'Setoran tersimpan, terverifikasi otomatis, dan progres juz diperbarui.'
            : 'Setoran berhasil dicatat dan menunggu verifikasi manual.';

        if ($request->input('return_to') === 'input') {
            return redirect()
                ->route('user.tahfidz.input.index', ['userId' => $userId, 'student_id' => $validated['student_id']])
                ->with('success', $message);
        }

        return redirect()->route('user.tahfidz.setoran.index', ['userId' => $userId, 'group_id' => $group->id])
            ->with('success', $message);
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $setoran = TahfidzSetoran::findOrFail($id);
        $this->authorizeInput($request, $setoran->group);
        abort_if($setoran->verification_status === 'verified', 403, 'Setoran yang sudah diverifikasi tidak dapat diedit.');

        $groups = $this->accessibleGroups($request);
        $group = $setoran->group;
        $students = $this->members($group);
        $surahs = TahfidzSurahMaster::orderBy('number')->get(['number', 'name_latin', 'name_arabic']);

        return view('tahfidz.setoran.form', compact('userId', 'groups', 'group', 'students', 'setoran', 'surahs'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $setoran = TahfidzSetoran::findOrFail($id);
        $this->authorizeInput($request, $setoran->group);
        abort_if($this->isLocked($setoran), 403, 'Setoran yang sudah diverifikasi manual tidak dapat diedit.');

        $validated = $this->validateSetoran($request);
        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        $this->authorizeInput($request, $group);
        $this->assertMember($group, $validated['student_id']);

        $setoran->update($this->payload($validated, $group, $request));

        if ($setoran->verification_status === 'verified') {
            $this->progress->recalculateForSetoran($setoran);
        }

        if ($request->input('return_to') === 'input') {
            return redirect()
                ->route('user.tahfidz.input.index', ['userId' => $userId, 'student_id' => $validated['student_id']])
                ->with('success', 'Setoran hafalan diperbarui.');
        }

        if ($request->input('return_to') === 'detail') {
            return redirect()
                ->route('user.tahfidz.santri.show', ['userId' => $userId, 'studentId' => $validated['student_id']])
                ->with('success', 'Setoran hafalan diperbarui.');
        }

        return redirect()->route('user.tahfidz.setoran.index', ['userId' => $userId, 'group_id' => $group->id])
            ->with('success', 'Setoran diperbarui.');
    }

    public function verify(Request $request, string $userId, string $id)
    {
        $setoran = TahfidzSetoran::findOrFail($id);
        $group = $setoran->group;
        abort_unless($this->access->canVerifySetoran($request->user(), $group), 403, 'Anda tidak berwenang memverifikasi setoran ini.');

        $setoran->forceFill([
            'verification_status' => 'verified',
            'verified_by' => $request->user()?->id,
            'verified_at' => now(),
            'verification_notes' => $request->input('verification_notes'),
        ])->save();

        $this->progress->recalculateForSetoran($setoran);

        return back()->with('success', 'Setoran diverifikasi dan progres juz diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $setoran = TahfidzSetoran::findOrFail($id);
        $this->authorizeInput($request, $setoran->group);
        abort_if($this->isLocked($setoran), 403, 'Setoran yang sudah diverifikasi manual tidak dapat dihapus.');
        $studentId = $setoran->student_id;
        $academicYearId = $setoran->academic_year_id;
        $groupId = $setoran->tahfidz_group_id;
        $setoran->delete();
        $this->progress->recalculateStudent($studentId, $academicYearId, $groupId);

        return back()->with('success', 'Setoran dihapus.');
    }

    private function accessibleGroups(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = TahfidzGroup::query()
            ->with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId)
                || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $user->id)->where('is_active', true)->where('can_input_setoran', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function members(TahfidzGroup $group)
    {
        return TahfidzGroupMember::with('student:id,name,nis')
            ->where('tahfidz_group_id', $group->id)
            ->where('status', 'aktif')
            ->get()
            ->pluck('student')
            ->filter()
            ->sortBy('name')
            ->values();
    }

    private function validateSetoran(Request $request): array
    {
        return $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'student_id' => 'required|exists:students,id',
            'setoran_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1|max:6',
            'setoran_type' => 'required|in:ziyadah,murajaah,tikror,robet',
            'metode_pembelajaran' => 'nullable|string|max:100',
            'juz' => 'nullable|integer|min:1|max:30',
            'surah_start_name' => 'nullable|string|max:100',
            'ayat_start' => 'nullable|integer|min:0',
            'surah_end_name' => 'nullable|string|max:100',
            'ayat_end' => 'nullable|integer|min:0',
            'halaman_start' => 'nullable|numeric|min:0|max:604',
            'baris_start' => 'nullable|integer|min:1|max:20',
            'halaman_end' => 'nullable|numeric|min:0|max:604',
            'baris_end' => 'nullable|integer|min:1|max:20',
            'jumlah_halaman' => 'nullable|numeric|min:0|max:604',
            'jumlah_baris' => 'nullable|integer|min:0|max:10000',
            'hasil_hafalan' => 'nullable|numeric|min:0|max:100',
            'khofi' => 'nullable|numeric|min:0|max:100',
            'jali' => 'nullable|numeric|min:0|max:100',
            'nilai_setoran' => 'nullable|numeric|min:0|max:100',
            'capaian_target' => 'nullable|in:tercapai,belum_tercapai,melampaui',
            'keterangan_capaian' => 'nullable|string|max:2000',
            'tikrar_task' => 'nullable|string|max:3000',
            'tikrar_repetitions' => 'nullable|integer|min:0|max:1000',
            'nilai_tikrar' => 'nullable|numeric|min:0|max:100',
            'robet_status' => 'nullable|in:siap,belum_siap,perlu_diulang',
            'hasil_hafalan_catatan' => 'nullable|string|max:3000',
            'lahn_khofi_count' => 'nullable|integer|min:0|max:1000',
            'lahn_jali_count' => 'nullable|integer|min:0|max:1000',
            'nilai_lahn' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:lulus,ulang,ditunda',
            'catatan_guru' => 'nullable|string|max:3000',
        ]);
    }

    private function payload(array $validated, TahfidzGroup $group, Request $request): array
    {
        $scores = collect([$validated['hasil_hafalan'] ?? null, $validated['khofi'] ?? null, $validated['jali'] ?? null])->filter(fn ($v) => $v !== null && $v !== '');
        $nilai = $validated['nilai_setoran'] ?? ($scores->isNotEmpty() ? round((float) $scores->avg(), 2) : null);

        $position = $this->resolvePosition($validated);
        $halamanStart = $validated['halaman_start'] ?? $position['page_start'] ?? null;
        $halamanEnd = $validated['halaman_end'] ?? $position['page_end'] ?? $halamanStart;
        $barisStart = $validated['baris_start'] ?? $position['line_start'] ?? null;
        $barisEnd = $validated['baris_end'] ?? $position['line_end'] ?? $barisStart;

        $jumlahHalaman = $validated['jumlah_halaman'] ?? null;
        if ($jumlahHalaman === null && $halamanStart !== null && $halamanEnd !== null) {
            $jumlahHalaman = max(0, (float) $halamanEnd - (float) $halamanStart + 1);
        }

        return array_merge($validated, [
            'teacher_id' => $request->user()?->id,
            'academic_year_id' => $group->academic_year_id,
            'halaman_start' => $halamanStart,
            'baris_start' => $barisStart,
            'halaman_end' => $halamanEnd,
            'baris_end' => $barisEnd,
            'jumlah_halaman' => $jumlahHalaman,
            'nilai_setoran' => $nilai,
        ]);
    }

    private function resolvePosition(array $validated): ?array
    {
        $surahStart = $this->surahNumber($validated['surah_start_name'] ?? null);
        $surahEnd = $this->surahNumber($validated['surah_end_name'] ?? null);
        $ayatStart = $validated['ayat_start'] ?? null;

        if (! $surahStart || ! $ayatStart) {
            return null;
        }

        return app(QuranPositionService::class)->range(
            $surahStart,
            (int) $ayatStart,
            $surahEnd,
            isset($validated['ayat_end']) ? (int) $validated['ayat_end'] : null,
        );
    }

    private function surahNumber(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $number = TahfidzSurahMaster::query()
            ->where('name_latin', $name)
            ->value('number');

        return $number ? (int) $number : null;
    }

    private function isLocked(TahfidzSetoran $setoran): bool
    {
        return $setoran->verification_status === 'verified'
            && $setoran->verification_notes !== 'auto-verified';
    }

    private function assertMember(TahfidzGroup $group, string $studentId): void
    {
        abort_unless(
            TahfidzGroupMember::where('tahfidz_group_id', $group->id)->where('student_id', $studentId)->where('status', 'aktif')->exists(),
            422,
            'Santri bukan anggota aktif halaqah ini.'
        );
    }

    private function authorizeInput(Request $request, TahfidzGroup $group): void
    {
        abort_unless($this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mencatat setoran halaqah ini.');
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
