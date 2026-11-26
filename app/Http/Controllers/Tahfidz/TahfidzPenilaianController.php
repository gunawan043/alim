<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\TahfidzGradebook;
use App\Models\TahfidzGroup;
use App\Services\TahfidzAccess;
use App\Services\TahfidzGradebookCalculator;
use App\Services\TahfidzGradebookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Buku nilai tahfidz per halaqah.
 *
 * Nilai mapel tahfidz TIDAK diambil dari rata-rata setoran dan TIDAK ditulis
 * ke buku mapel umum (`admin_nilai_sumatif`). Nilainya diisi guru di sini
 * dengan empat kolom: Ziyadah, Rabt, STS, dan SAS — sesuai format penilaian
 * yang berlaku untuk tahfidz. Bobot tiap kolom diatur guru sendiri, sama
 * seperti buku nilai mapel lain.
 *
 * Buku diikat ke halaqah (bukan rombel) karena anggota satu halaqah bisa
 * berasal dari beberapa rombel dalam satu tingkat. Saat masuk rapor, nilai
 * dipetakan ke rombel masing-masing santri.
 */
class TahfidzPenilaianController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzGradebookService $books,
        private readonly TahfidzGradebookCalculator $calculator,
    ) {}

    public function index(Request $request, string $userId)
    {
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();

        if (! $academicYear) {
            return view('tahfidz.penilaian.index', [
                'userId' => $userId,
                'groups' => collect(),
                'group' => null,
                'books' => collect(),
                'book' => null,
                'subjects' => collect(),
                'rows' => collect(),
                'members' => collect(),
                'semester' => 'ganjil',
                'academicYear' => null,
                'weightErrors' => [],
            ]);
        }

        $semester = in_array($request->input('semester'), ['ganjil', 'genap'], true)
            ? $request->input('semester')
            : ($academicYear->semester ?: 'ganjil');

        $groups = $this->accessibleGroups($request, $academicYear);
        $groupId = $request->input('group_id', $groups->first()?->id);
        $group = $groups->firstWhere('id', $groupId) ?? $groups->first();

        $books = collect();
        $book = null;
        $rows = collect();
        $members = collect();

        if ($group) {
            $books = $this->booksForGroup($group, $academicYear, $semester);

            if ($books->isNotEmpty()) {
                $subjectId = $request->input('subject_id', $books->first()->subject_id);
                $book = $books->firstWhere('subject_id', $subjectId) ?? $books->first();

                $members = $this->books->activeMembers($group, $academicYear->id);
                $scores = $this->books->existingScores($book);

                $rows = $members->map(fn ($member) => [
                    'member' => $member,
                    'score' => $scores->get($member['student_id']),
                ]);
            }
        }

        return view('tahfidz.penilaian.index', [
            'userId' => $userId,
            'groups' => $groups,
            'group' => $group,
            'books' => $books,
            'book' => $book,
            'rows' => $rows,
            'members' => $members,
            'semester' => $semester,
            'academicYear' => $academicYear,
            'weightErrors' => [],
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'tahfidz_gradebook_id' => 'required|exists:tahfidz_gradebooks,id',
            'semester' => 'required|in:ganjil,genap',
            'scores' => 'array',
            'scores.*.ziyadah' => 'nullable|numeric|min:0|max:100',
            'scores.*.rabt' => 'nullable|numeric|min:0|max:100',
            'scores.*.sts' => 'nullable|numeric|min:0|max:100',
            'scores.*.sas' => 'nullable|numeric|min:0|max:100',
        ]);

        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        abort_unless($this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mengisi nilai halaqah ini.');

        $book = TahfidzGradebook::findOrFail($validated['tahfidz_gradebook_id']);
        abort_unless(
            $book->tahfidz_group_id === $group->id && $book->semester === $validated['semester'],
            422,
            'Buku nilai tidak sesuai halaqah atau semester yang dipilih.',
        );

        $academicYear = AcademicYear::where('is_active', true)->first();
        abort_unless($academicYear, 422, 'Belum ada tahun ajaran aktif.');

        $members = $this->books->activeMembers($group, $academicYear->id);

        // Nilai ditulis dulu, lalu bobot (bila dikirim) diverifikasi. Urutan ini
        // membuat pesan kesalahan bobot tidak membuang nilai yang sudah diisi.
        $saved = $this->books->saveScores($book, $validated['scores'] ?? [], $members);

        $redirect = redirect()
            ->route('user.tahfidz.penilaian.index', [
                'userId' => $userId,
                'group_id' => $group->id,
                'subject_id' => $book->subject_id,
                'semester' => $validated['semester'],
            ]);

        if (! $request->has('weights')) {
            return $redirect->with('success', 'Nilai tersimpan untuk '.$saved.' santri.');
        }

        $weights = [
            'ziyadah' => $request->input('weights.ziyadah'),
            'rabt' => $request->input('weights.rabt'),
            'sts' => $request->input('weights.sts'),
            'sas' => $request->input('weights.sas'),
        ];

        $errors = $this->calculator->validateWeights($weights);

        if ($errors !== []) {
            return $redirect
                ->with('error', 'Nilai tersimpan, tetapi bobot tidak diubah: '.implode(' ', $errors));
        }

        DB::transaction(function () use ($book, $weights) {
            $book->update([
                'weight_ziyadah' => (float) $weights['ziyadah'],
                'weight_rabt' => (float) $weights['rabt'],
                'weight_sts' => (float) $weights['sts'],
                'weight_sas' => (float) $weights['sas'],
            ]);

            // Bobot baru harus langsung berlaku untuk nilai yang sudah ada,
            // agar rapor memakai angka terbaru tanpa menunggu input ulang.
            $book->refresh()->recalcAll();
        });

        return $redirect->with('success', 'Nilai dan bobot tersimpan. NR Final dihitung ulang untuk seluruh santri.');
    }

    /**
     * Buku nilai halaqah pada periode ini, satu per mapel tahfidz.
     */
    private function booksForGroup(TahfidzGroup $group, AcademicYear $academicYear, string $semester)
    {
        return TahfidzGradebook::with('subject')
            ->where('tahfidz_group_id', $group->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($book) => $book->subject?->name)
            ->values();
    }

    private function accessibleGroups(Request $request, AcademicYear $academicYear)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $query = TahfidzGroup::query()
            ->with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
            ->where('academic_year_id', $academicYear->id);

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
