<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzSurahMaster;
use App\Services\Pdf\DocumentPdfService;
use App\Services\QuranPositionService;
use App\Services\TahfidzAccess;
use App\Services\TahfidzPresenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TahfidzMonitoringSheetController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $data = $this->sheetData($request, $userId);

        return view('tahfidz.monitoring.sheet', $data);
    }

    public function print(Request $request, string $userId)
    {
        $data = $this->sheetData($request, $userId);

        abort_unless($data['selectedMember'], 404, 'Pilih santri terlebih dahulu.');

        $student = $data['selectedStudent'];
        $monthLabel = $data['monthLabel'];

        return app(DocumentPdfService::class)->fromView(
            'tahfidz.monitoring.sheet-print',
            $data,
            'Lembar-Monitoring-'.$student->name.'-'.$monthLabel,
            'landscape',
        );
    }

    public function store(Request $request, string $userId)
    {
        $validated = $this->validateEntry($request);
        $group = TahfidzGroup::with('school:id,name')->findOrFail($validated['tahfidz_group_id']);
        $this->authorizeInput($request, $group);
        $this->assertMember($group, $validated['student_id']);

        $data = $this->payload($validated, $group, $request);
        $data['verification_status'] = 'pending';

        $setoran = TahfidzSetoran::create($data);

        return $this->redirectToSheet($request, $userId, $setoran, 'Catatan monitoring berhasil disimpan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $setoran = TahfidzSetoran::findOrFail($id);
        $this->authorizeInput($request, $setoran->group);

        abort_if(
            $setoran->verification_status === 'verified' && $setoran->verification_notes !== 'auto-verified',
            403,
            'Catatan yang sudah diverifikasi manual tidak dapat diedit.'
        );

        $validated = $this->validateEntry($request);
        $group = TahfidzGroup::with('school:id,name')->findOrFail($validated['tahfidz_group_id']);
        $this->authorizeInput($request, $group);
        $this->assertMember($group, $validated['student_id']);

        $data = $this->payload($validated, $group, $request);
        $data['verification_status'] = $setoran->verification_status;
        $data['nilai_setoran'] = $setoran->nilai_setoran;

        $setoran->update($data);

        return $this->redirectToSheet($request, $userId, $setoran, 'Catatan monitoring berhasil diperbarui.');
    }

    private function sheetData(Request $request, string $userId): array
    {
        $this->authorizeView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $groups = $this->accessibleGroups($request, $activeYear?->id);
        $groupIds = $groups->pluck('id');

        $members = TahfidzGroupMember::with([
            'student:id,name,nis,school_id,origin_school_id',
            'group:id,name,school_id,teacher_id,academic_year_id',
            'group.teacher:id,name',
        ])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->where('status', 'aktif')
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->get()
            ->sortBy(fn ($member) => $member->student?->name)
            ->values();

        $students = $members
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $presenceToday = app(TahfidzPresenceService::class)
            ->forStudents($students->pluck('id'), now());

        $selectedStudentId = (string) $request->input('student_id', '');
        $selectedGroupId = (string) $request->input('group_id', '');
        $selectedMember = $members->first(function (TahfidzGroupMember $member) use ($selectedStudentId, $selectedGroupId) {
            return (string) $member->student_id === $selectedStudentId
                && ($selectedGroupId === '' || (string) $member->tahfidz_group_id === $selectedGroupId);
        });

        $monthInput = $this->monthInput($request);
        $month = Carbon::createFromFormat('Y-m-d', $monthInput.'-01')->startOfDay();
        $monthLabel = $month->copy()->locale('id')->translatedFormat('F Y');

        $entries = collect();
        $weekGroups = collect();
        $maxWeek = 4;

        if ($selectedMember) {
            $entries = TahfidzSetoran::with(['teacher:id,name', 'verifier:id,name'])
                ->where('student_id', $selectedMember->student_id)
                ->where('tahfidz_group_id', $selectedMember->tahfidz_group_id)
                ->whereBetween('setoran_date', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ])
                ->orderBy('setoran_date')
                ->orderBy('created_at')
                ->get();

            $maxWeek = max(
                4,
                $this->weekOfMonth($month->copy()->endOfMonth()),
                (int) ($entries->max(fn ($entry) => $this->weekOfMonth($entry->setoran_date)) ?? 0),
            );

            $weekGroups = collect(range(1, $maxWeek))->mapWithKeys(
                fn (int $week) => [$week => collect()]
            );

            foreach ($entries as $entry) {
                $week = $this->weekOfMonth($entry->setoran_date);
                if (! isset($weekGroups[$week])) {
                    $weekGroups[$week] = collect();
                }
                $weekGroups[$week]->push($entry);
            }

            $weekGroups = $weekGroups->sortKeys();
        }

        $canInput = $selectedMember
            ? $this->access->canInputSetoran($request->user(), $selectedMember->group)
            : false;

        $surahs = TahfidzSurahMaster::query()
            ->orderBy('number')
            ->get(['number', 'name_latin', 'name_arabic']);

        return compact(
            'userId', 'academicYears', 'activeYear', 'groups', 'members', 'students',
            'selectedStudentId', 'selectedGroupId', 'selectedMember', 'monthInput',
            'month', 'monthLabel', 'entries', 'weekGroups', 'maxWeek', 'canInput', 'surahs',
            'presenceToday'
        ) + [
            'selectedStudent' => $selectedMember?->student,
            'selectedPresence' => $selectedMember
                ? $presenceToday->get((string) $selectedMember->student_id)
                : null,
        ];
    }

    private function validateEntry(Request $request): array
    {
        return $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'student_id' => 'required|exists:students,id',
            'setoran_date' => 'required|date',
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
            'capaian_target' => 'required|in:belum_tercapai,tercapai,melampaui',
            'keterangan_capaian' => 'nullable|string|max:2000',
            'tikrar_task' => 'nullable|string|max:3000',
            'tikrar_repetitions' => 'nullable|integer|min:0|max:1000',
            'nilai_tikrar' => 'nullable|numeric|min:0|max:100',
            'robet_status' => 'nullable|in:siap,belum_siap,perlu_diulang',
            'hasil_hafalan_catatan' => 'nullable|string|max:3000',
            'lahn_khofi_count' => 'nullable|integer|min:0|max:1000',
            'lahn_jali_count' => 'nullable|integer|min:0|max:1000',
            'nilai_lahn' => 'nullable|numeric|min:0|max:100',
            'catatan_guru' => 'nullable|string|max:3000',
        ]);
    }

    private function payload(array $validated, TahfidzGroup $group, Request $request): array
    {
        $date = Carbon::parse($validated['setoran_date']);
        $position = $this->resolvePosition($validated);
        $halamanStart = $validated['halaman_start'] ?? $position['page_start'] ?? null;
        $halamanEnd = $validated['halaman_end'] ?? $position['page_end'] ?? $halamanStart;
        $barisStart = $validated['baris_start'] ?? $position['line_start'] ?? null;
        $barisEnd = $validated['baris_end'] ?? $position['line_end'] ?? $barisStart;
        $jumlahHalaman = null;

        if ($halamanStart !== null && $halamanEnd !== null) {
            $jumlahHalaman = max(0, (float) $halamanEnd - (float) $halamanStart + 1);
        }

        return [
            'tahfidz_group_id' => $group->id,
            'student_id' => $validated['student_id'],
            'teacher_id' => $request->user()?->id,
            'academic_year_id' => $group->academic_year_id,
            'setoran_date' => $date->toDateString(),
            'week_number' => $this->weekOfMonth($date),
            'setoran_type' => $validated['setoran_type'],
            'metode_pembelajaran' => $validated['metode_pembelajaran'] ?? null,
            'surah_start_name' => $validated['surah_start_name'] ?? null,
            'ayat_start' => $validated['ayat_start'] ?? null,
            'surah_end_name' => $validated['surah_end_name'] ?? null,
            'ayat_end' => $validated['ayat_end'] ?? null,
            'juz' => $validated['juz'] ?? null,
            'halaman_start' => $halamanStart,
            'baris_start' => $barisStart,
            'halaman_end' => $halamanEnd,
            'baris_end' => $barisEnd,
            'jumlah_halaman' => $jumlahHalaman,
            'capaian_target' => $validated['capaian_target'],
            'keterangan_capaian' => $validated['keterangan_capaian'] ?? null,
            'tikrar_task' => $validated['tikrar_task'] ?? null,
            'tikrar_repetitions' => $validated['tikrar_repetitions'] ?? null,
            'nilai_tikrar' => $validated['nilai_tikrar'] ?? null,
            'robet_status' => $validated['robet_status'] ?? null,
            'hasil_hafalan_catatan' => $validated['hasil_hafalan_catatan'] ?? null,
            'lahn_khofi_count' => (int) ($validated['lahn_khofi_count'] ?? 0),
            'lahn_jali_count' => (int) ($validated['lahn_jali_count'] ?? 0),
            'nilai_lahn' => $validated['nilai_lahn'] ?? null,
            'nilai_setoran' => null,
            'status' => 'lulus',
            'catatan_guru' => $validated['catatan_guru'] ?? null,
        ];
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

    private function redirectToSheet(Request $request, string $userId, TahfidzSetoran $setoran, string $message)
    {
        return redirect()
            ->route('user.tahfidz.monitoring.sheet', [
                'userId' => $userId,
                'student_id' => $setoran->student_id,
                'group_id' => $setoran->tahfidz_group_id,
                'month' => $setoran->setoran_date?->format('Y-m') ?: $this->monthInput($request),
            ])
            ->with('success', $message);
    }

    private function monthInput(Request $request): string
    {
        $month = (string) $request->input('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)
            ? $month
            : now()->format('Y-m');
    }

    private function weekOfMonth(Carbon $date): int
    {
        $first = $date->copy()->startOfMonth();
        $offset = $first->dayOfWeekIso - 1;

        return (int) (intdiv($offset + $date->day - 1, 7) + 1);
    }

    private function accessibleGroups(Request $request, ?string $academicYearId): Collection
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        $query = TahfidzGroup::query()
            ->with(['school:id,name', 'teacher:id,name'])
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId)
                || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $schoolId)
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat lembar monitoring Tahfidz.'
        );
    }

    private function authorizeInput(Request $request, TahfidzGroup $group): void
    {
        abort_unless(
            $this->access->canInputSetoran($request->user(), $group),
            403,
            'Anda tidak berwenang mengubah catatan monitoring halaqah ini.'
        );
    }

    private function assertMember(TahfidzGroup $group, string $studentId): void
    {
        abort_unless(
            TahfidzGroupMember::where('tahfidz_group_id', $group->id)
                ->where('student_id', $studentId)
                ->where('status', 'aktif')
                ->exists(),
            422,
            'Santri bukan anggota aktif halaqah ini.'
        );
    }
}
