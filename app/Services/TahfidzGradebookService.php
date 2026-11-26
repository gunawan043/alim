<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use App\Models\Subject;
use App\Models\TahfidzGradebook;
use App\Models\TahfidzGradebookScore;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use Illuminate\Support\Collection;

/**
 * Buku nilai tahfidz berbasis HALAQAH.
 *
 * Mapel umum: satu buku per (guru × mapel × rombel). Tahfidz tidak begitu —
 * daftar santri mengikuti keanggotaan halaqah, karena satu halaqah boleh
 * berisi santri dari beberapa rombel dalam satu tingkat.
 *
 * Anggota yang sudah keluar (status `pindah_halaqah`) tidak lagi tampil,
 * sesuai keputusan pemilik sistem. Nilai yang pernah diisi tetap tersimpan
 * di baris lama, tetapi tidak muncul pada daftar input.
 */
class TahfidzGradebookService
{
    /** Status keanggotaan yang dianggap masih aktif di halaqah. */
    public const ACTIVE_MEMBER_STATUSES = ['aktif', 'active'];

    /**
     * Bobot awal NR Final format tahfidz.
     *
     * Ziyadah (hafalan baru) paling besar karena menjadi capaian utama,
     * sedangkan rabt, STS, dan SAS masing-masing menutup sisa bobot.
     */
    public const DEFAULT_WEIGHTS = [
        'ziyadah' => 40.0,
        'rabt' => 20.0,
        'sts' => 20.0,
        'sas' => 20.0,
    ];

    /**
     * Halaqah tempat guru ini mengajar, pada tahun ajaran aktif.
     *
     * Guru bisa tercatat sebagai `teacher_id` halaqah atau lewat tabel
     * `tahfidz_group_teachers` (peran pengajar), jadi keduanya diperiksa.
     */
    public function groupsForTeacher(string $teacherId, ?string $schoolId = null, ?string $academicYearId = null): Collection
    {
        $academicYearId ??= AcademicYear::where('is_active', true)->value('id');

        return TahfidzGroup::query()
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                    ->orWhereHas('teachers', fn ($t) => $t
                        ->where('user_id', $teacherId)
                        ->where('is_active', true));
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Buku nilai halaqah untuk daftar "Buku Nilai Saya".
     *
     * Guru tahfidz TIDAK mengajar mapel umum, jadi daftar ini murni buku
     * halaqah — tidak dicampur dengan buku mapel per rombel. Satu baris per
     * halaqah; mapel tahfidz hanya satu sehingga mapel tidak perlu dipilih
     * (bila data lama memuat lebih dari satu, baris tetap dipisah mapel).
     *
     * @return Collection<int, array{book: TahfidzGradebook, group: TahfidzGroup, subject: ?Subject, member_count: int}>
     */
    public function bookRowsForTeacher(string $teacherId, ?string $schoolId = null, ?string $academicYearId = null): Collection
    {
        $books = $this->booksForTeacher($teacherId, $schoolId, $academicYearId)->flatten();

        if ($books->isEmpty()) {
            return collect();
        }

        $groups = TahfidzGroup::query()
            ->with(['studyGroup:id,name', 'school:id,name'])
            ->whereIn('id', $books->pluck('tahfidz_group_id')->unique())
            ->get()
            ->keyBy('id');

        // Jumlah anggota aktif per halaqah, agar guru tahu isi buku sebelum membuka.
        $memberCounts = TahfidzGroupMember::query()
            ->whereIn('tahfidz_group_id', $groups->keys())
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->whereIn('status', self::ACTIVE_MEMBER_STATUSES)
            ->selectRaw('tahfidz_group_id, COUNT(*) as total')
            ->groupBy('tahfidz_group_id')
            ->pluck('total', 'tahfidz_group_id');

        return $books
            ->unique(fn (TahfidzGradebook $book) => $book->tahfidz_group_id.'|'.$book->subject_id)
            ->map(fn (TahfidzGradebook $book) => [
                'book' => $book,
                'group' => $groups->get($book->tahfidz_group_id),
                'subject' => $book->subject,
                'member_count' => (int) ($memberCounts[$book->tahfidz_group_id] ?? 0),
            ])
            ->filter(fn ($row) => $row['group'] !== null)
            ->sortBy(fn ($row) => ($row['group']->name ?? '').' '.($row['subject']?->name ?? ''))
            ->values();
    }

    /**
     * Buku nilai (per halaqah) untuk guru, dikelompokkan per semester.
     *
     * Buku dibuat otomatis saat pertama dibuka: satu buku per halaqah untuk
     * setiap mapel berformat tahfidz di sekolah yang sama. Tanpa ini guru
     * harus membuat buku secara manual padahal konteksnya sudah jelas.
     *
     * @return Collection<string, Collection<int, TahfidzGradebook>>
     */
    public function booksForTeacher(string $teacherId, ?string $schoolId = null, ?string $academicYearId = null): Collection
    {
        $academicYearId ??= AcademicYear::where('is_active', true)->value('id');

        if (! $academicYearId) {
            return collect();
        }

        $groups = $this->groupsForTeacher($teacherId, $schoolId, $academicYearId);

        if ($groups->isEmpty()) {
            return collect();
        }

        $books = collect();

        foreach ($groups as $group) {
            foreach ($this->tahfidzSubjectsFor($group, $academicYearId) as $subject) {
                $book = TahfidzGradebook::firstOrCreate(
                    [
                        'tahfidz_group_id' => $group->id,
                        'subject_id' => $subject->id,
                        'academic_year_id' => $academicYearId,
                        'semester' => $this->semesterFor($group, $academicYearId),
                    ],
                    [
                        'school_id' => $group->school_id,
                        'is_active' => true,
                        // Bobot awal ditulis eksplisit: kolom default database
                        // tidak ikut termuat pada model yang baru dibuat, dan
                        // bobot 0 membuat NR Final selalu kosong.
                        'weight_ziyadah' => self::DEFAULT_WEIGHTS['ziyadah'],
                        'weight_rabt' => self::DEFAULT_WEIGHTS['rabt'],
                        'weight_sts' => self::DEFAULT_WEIGHTS['sts'],
                        'weight_sas' => self::DEFAULT_WEIGHTS['sas'],
                    ],
                );

                $books->push($book);
            }
        }

        return $books
            ->unique('id')
            ->sortBy([['semester', 'asc'], ['tahfidz_group_id', 'asc']])
            ->groupBy('semester');
    }

    /**
     * Pastikan buku halaqah ada untuk setiap mapel tahfidz di sekolahnya.
     *
     * Dipakai halaman Buku Nilai Halaqah: guru membuka halaqah lalu langsung
     * menilai, tanpa perlu membuat buku lebih dulu.
     *
     * @return Collection<int, TahfidzGradebook>
     */
    public function ensureBooks(TahfidzGroup $group, string $academicYearId): Collection
    {
        return $this->tahfidzSubjectsFor($group, $academicYearId)
            ->map(fn (Subject $subject) => TahfidzGradebook::firstOrCreate(
                [
                    'tahfidz_group_id' => $group->id,
                    'subject_id' => $subject->id,
                    'academic_year_id' => $academicYearId,
                    'semester' => $this->semesterFor($group, $academicYearId),
                ],
                [
                    'school_id' => $group->school_id,
                    'is_active' => true,
                    'weight_ziyadah' => self::DEFAULT_WEIGHTS['ziyadah'],
                    'weight_rabt' => self::DEFAULT_WEIGHTS['rabt'],
                    'weight_sts' => self::DEFAULT_WEIGHTS['sts'],
                    'weight_sas' => self::DEFAULT_WEIGHTS['sas'],
                ],
            ))
            ->values();
    }

    /**
     * Mapel berformat tahfidz yang dipakai halaqah ini.
     *
     * Mapel ditentukan dari penanda `penilaian_format`, bukan dari nama —
     * agar nama mapel yang ditulis berbeda ejaan tidak membuat buku hilang.
     * Bila sekolah belum punya mapel bertanda tahfidz, daftar kosong dan
     * guru diarahkan menandai mapel lebih dulu (tidak menebak).
     */
    public function tahfidzSubjectsFor(TahfidzGroup $group, string $academicYearId): Collection
    {
        return Subject::query()
            ->where('penilaian_format', TahfidzSubjectResolver::FORMAT_TAHFIDZ)
            ->where('school_id', $group->school_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Daftar santri yang menjadi anggota aktif halaqah, beserta rombelnya.
     * Rombel dipakai untuk memetakan nilai ke rapor masing-masing.
     *
     * @return Collection<int, array{student_id: string, name: string, nis: ?string, study_group_id: ?string, study_group_name: ?string, attendance_number: ?int}>
     */
    public function activeMembers(TahfidzGroup $group, string $academicYearId): Collection
    {
        $members = TahfidzGroupMember::query()
            ->with(['student:id,name,nis'])
            ->where('tahfidz_group_id', $group->id)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('status', self::ACTIVE_MEMBER_STATUSES)
            ->get();

        $studentIds = $members->pluck('student_id')->unique()->values();

        if ($studentIds->isEmpty()) {
            return collect();
        }

        // Rombel diambil dari riwayat kelas tahun ajaran ini — sumber yang
        // sama dengan rapor, sehingga nilai masuk ke rombel yang benar.
        $histories = StudentClassHistory::query()
            ->with('studyGroup:id,name')
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get()
            ->keyBy('student_id');

        return $members
            ->map(function ($member) use ($histories) {
                $history = $histories->get($member->student_id);

                return [
                    'student_id' => $member->student_id,
                    'name' => $member->student?->name ?? '—',
                    'nis' => $member->student?->nis,
                    'study_group_id' => $history?->study_group_id,
                    'study_group_name' => $history?->studyGroup?->name,
                    'attendance_number' => $history?->attendance_number,
                ];
            })
            ->sortBy('attendance_number')
            ->values();
    }

    /**
     * Baris nilai yang sudah tersimpan, diindeks per student_id.
     */
    public function existingScores(TahfidzGradebook $book): Collection
    {
        return TahfidzGradebookScore::query()
            ->where('tahfidz_gradebook_id', $book->id)
            ->get()
            ->keyBy('student_id');
    }

    /**
     * Simpan nilai dari input guru.
     *
     * @param  array<string, array{ziyadah?: mixed, rabt?: mixed, sts?: mixed, sas?: mixed, ket?: mixed}>  $rows
     * @param  Collection<int, array{student_id: string, study_group_id: ?string}>  $members
     */
    public function saveScores(TahfidzGradebook $book, array $rows, Collection $members): int
    {
        $calculator = app(TahfidzGradebookCalculator::class);
        $memberMap = $members->keyBy('student_id');
        $saved = 0;

        foreach ($rows as $studentId => $row) {
            $member = $memberMap->get($studentId);

            // Santri yang sudah keluar dari halaqah tidak boleh menambah nilai
            // baru — mencegah nilai terus bertambah untuk orang yang sudah
            // tidak ada di daftar.
            if (! $member) {
                continue;
            }

            $values = [
                'ziyadah' => $this->numberOrNull($row['ziyadah'] ?? null),
                'rabt' => $this->numberOrNull($row['rabt'] ?? null),
                'sts' => $this->numberOrNull($row['sts'] ?? null),
                'sas' => $this->numberOrNull($row['sas'] ?? null),
            ];

            $existing = TahfidzGradebookScore::query()
                ->where('tahfidz_gradebook_id', $book->id)
                ->where('student_id', $studentId)
                ->first();

            $hasValue = collect($values)->contains(fn ($v) => $v !== null);
            $hasExistingValue = $existing && collect([
                $existing->ziyadah, $existing->rabt, $existing->sts, $existing->sas,
            ])->contains(fn ($v) => $v !== null);

            if (! $hasValue && ! $hasExistingValue) {
                continue;
            }

            $score = $existing ?: new TahfidzGradebookScore([
                'tahfidz_gradebook_id' => $book->id,
                'student_id' => $studentId,
            ]);

            $score->fill($values);
            $score->study_group_id = $member['study_group_id'] ?? $score->study_group_id;
            $score->ket = $this->stringOrNull($row['ket'] ?? null);

            $calculator->apply($score, $book);
            $score->save();
            $saved++;
        }

        return $saved;
    }

    private function semesterFor(TahfidzGroup $group, string $academicYearId): string
    {
        $semester = AcademicYear::where('id', $academicYearId)->value('semester');

        return in_array($semester, ['ganjil', 'genap'], true) ? $semester : 'ganjil';
    }

    private function numberOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        if ($number < 0 || $number > 100) {
            return null;
        }

        return round($number, 2);
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, 100);
    }
}
