<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Observers\TeachingAssignmentObserver;
use App\Services\SchoolGroupService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Buat penugasan mengajar OTOMATIS dari data yang sudah ada:
 *   StudyGroup (rombel) × StudyGroupSubject (mapel per rombel) → TeachingAssignment + guru.
 *
 * Guru dipilih dari staf satuan pendidikan; preferensi jabatan mengikuti mapel
 * (tahfidz → Guru Tahfidz; hadits/agama → Guru Agama/Hadits; bahasa arab → Guru Bahasa Arab;
 * lainnya → Guru Umum/Kelas/Mapel), dibagi rata (round-robin beban terendah).
 *
 * Idempoten: satu mapel satu guru per rombel (kebijakan anti-ganda).
 */
class AssignTeachersCommand extends Command
{
    protected $signature = 'akademik:assign-teachers
        {--school= : Batasi ke satu sekolah (uuid)}
        {--ay= : Tahun ajaran (uuid) — default TA operasional}
        {--with-books : Sekalian provisioning buku nilai (lebih lambat)}
        {--rebalance : Seimbangkan beban guru (pindahkan kelebihan JP ke guru lain)}
        {--max-jp=24 : Batas maksimal JP/pekan per guru saat rebalance}
        {--fallback-all : Bila sekolah tidak punya guru sendiri, pakai seluruh guru (demo)}
        {--prune-foreign : Hapus penugasan yang gurunya bukan staf sekolah tersebut}';

    protected $description = 'Buat penugasan mengajar otomatis dari rombel & mapel yang sudah ada.';

    public function handle(): int
    {
        $ay = $this->option('ay')
            ? AcademicYear::find($this->option('ay'))
            : $this->resolveOperationalAy();

        if (! $ay) {
            $this->error('Tahun ajaran tidak ditemukan.');

            return self::FAILURE;
        }

        $schools = School::query()
            ->when($this->option('school'), fn ($q) => $q->where('id', $this->option('school')))
            ->orderBy('name')
            ->get();

        TeachingAssignmentObserver::$silent = true;
        TeachingAssignmentObserver::$provision = (bool) $this->option('with-books');

        $totalCreated = 0;
        $totalLinked = 0;
        $skippedSchools = 0;

        foreach ($schools as $school) {
            $rombels = StudyGroup::query()
                ->where('school_id', $school->id)
                ->where('academic_year_id', $ay->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            if ($rombels->isEmpty()) {
                continue;
            }

            $teachers = $this->teachersFor($school, (bool) $this->option('fallback-all'));

            if ($teachers->isEmpty()) {
                $this->warn("Lewati {$school->name}: tidak ada guru.");
                $skippedSchools++;
                continue;
            }

            $teachersByCategory = $this->categorizeTeachers($teachers);
            $load = []; // teacher_id → jumlah penugasan (untuk round-robin)

            // SK otomatis per sekolah+TA (dipakai sebagai payung penugasan).
            $decree = InstitutionDecree::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $ay->id,
                    'decree_type' => 'SK Pembagian Tugas',
                ],
                [
                    'id' => (string) Str::uuid(),
                    'decree_number' => 'AUTO/TGS/'.now()->format('Ymd').'/'.strtoupper(Str::random(4)),
                    'title' => 'SK Pembagian Tugas (Otomatis)',
                    'issued_date' => now()->format('Y-m-d'),
                    'effective_date' => now()->format('Y-m-d'),
                    'status' => 'active',
                ]
            );

            foreach ($rombels as $rombel) {
                $links = StudyGroupSubject::query()
                    ->with('subject:id,name,code')
                    ->where('study_group_id', $rombel->id)
                    ->where('academic_year_id', $ay->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($links as $link) {
                    if (! $link->subject) {
                        continue;
                    }

                    // Sudah ada guru untuk mapel+rombel ini? cukup tautkan.
                    $existing = TeachingAssignment::query()
                        ->where('school_id', $school->id)
                        ->where('academic_year_id', $ay->id)
                        ->where('study_group_id', $rombel->id)
                        ->where('subject_id', $link->subject_id)
                        ->where('status', 'active')
                        ->first();

                    if ($existing) {
                        if ((string) $link->teacher_id !== (string) $existing->teacher_id) {
                            $link->forceFill(['teacher_id' => $existing->teacher_id])->save();
                            $totalLinked++;
                        }
                        continue;
                    }

                    $teacher = $this->pickTeacher($link->subject->name, $teachersByCategory, $load);

                    if (! $teacher) {
                        continue;
                    }

                    TeachingAssignment::create([
                        'decree_id' => $decree->id,
                        'teacher_id' => $teacher->id,
                        'school_id' => $school->id,
                        'academic_year_id' => $ay->id,
                        'study_group_id' => $rombel->id,
                        'subject_id' => $link->subject_id,
                        'weekly_hours' => (int) max(0, min(40, (float) $link->weekly_hours)),
                        'role' => 'guru_mapel',
                        'status' => 'active',
                    ]);

                    $link->forceFill(['teacher_id' => $teacher->id])->save();

                    $load[$teacher->id] = ($load[$teacher->id] ?? 0) + 1;
                    $totalCreated++;
                }
            }
        }

        // Prune: hapus penugasan yang gurunya bukan staf sekolah (sisa fallback lintas sekolah).
        $totalPruned = 0;
        if ($this->option('prune-foreign')) {
            foreach ($schools as $school) {
                $ownIds = SchoolGroupService::userIdsForSchool($school->id);

                $foreign = TeachingAssignment::query()
                    ->where('school_id', $school->id)
                    ->where('academic_year_id', $ay->id)
                    ->when(! empty($ownIds), fn ($q) => $q->whereNotIn('teacher_id', $ownIds))
                    ->when(empty($ownIds), fn ($q) => $q)
                    ->get();

                foreach ($foreign as $assignment) {
                    StudyGroupSubject::query()
                        ->where('study_group_id', $assignment->study_group_id)
                        ->where('subject_id', $assignment->subject_id)
                        ->where('academic_year_id', $ay->id)
                        ->update(['teacher_id' => null]);

                    $assignment->delete();
                    $totalPruned++;
                }
            }

            $this->line("  Prune lintas sekolah: {$totalPruned} penugasan dihapus.");
        }

        // Rebalance: sebar beban agar memungkinkan jadwal tanpa bentrok guru.
        $totalMoved = 0;
        if ($this->option('rebalance')) {
            $maxJp = (int) $this->option('max-jp');

            foreach ($schools as $school) {
                $moved = $this->rebalanceSchool($school, $ay, max(6, $maxJp));
                $totalMoved += $moved;

                if ($moved > 0) {
                    $this->line("  Rebalance {$school->name}: {$moved} penugasan dipindah.");
                }
            }
        }

        TeachingAssignmentObserver::$silent = false;
        TeachingAssignmentObserver::$provision = true;

        $this->newLine();
        $this->info("Selesai [TA {$ay->name}]: {$totalCreated} penugasan dibuat, {$totalLinked} tautan mapel–rombel disinkronkan."
            .($totalPruned ? " {$totalPruned} penugasan lintas sekolah dihapus." : '')
            .($totalMoved ? " {$totalMoved} penugasan direbalance." : '')
            .($skippedSchools ? " {$skippedSchools} sekolah tanpa guru dilewati." : ''));

        return self::SUCCESS;
    }

    /**
     * Pindahkan kelebihan JP guru (di atas kapasitas) ke guru lain yang masih longgar.
     */
    private function rebalanceSchool(School $school, AcademicYear $ay, int $maxJp): int
    {
        $assignments = TeachingAssignment::query()
            ->with('subject:id,name')
            ->where('school_id', $school->id)
            ->where('academic_year_id', $ay->id)
            ->where('status', 'active')
            ->get();

        if ($assignments->isEmpty()) {
            return 0;
        }

        $teachers = $this->teachersFor($school);
        if ($teachers->isEmpty()) {
            return 0;
        }

        $byCategory = $this->categorizeTeachers($teachers);

        $load = [];
        foreach ($assignments as $a) {
            $load[$a->teacher_id] = ($load[$a->teacher_id] ?? 0) + (int) $a->weekly_hours;
        }

        $moved = 0;

        foreach ($assignments->sortByDesc('weekly_hours') as $assignment) {
            $teacherId = $assignment->teacher_id;
            $currentLoad = $load[$teacherId] ?? 0;

            if ($currentLoad <= $maxJp) {
                continue;
            }

            $jp = (int) $assignment->weekly_hours;
            $category = $this->categoryFor($assignment->subject->name ?? '');

            $candidates = ($byCategory[$category] ?? collect())
                ->merge($byCategory['semua'] ?? collect())
                ->unique('id')
                ->filter(fn ($t) => (string) $t->id !== (string) $teacherId && (($load[$t->id] ?? 0) + $jp) <= $maxJp)
                ->sortBy(fn ($t) => $load[$t->id] ?? 0);

            $target = $candidates->first();
            if (! $target) {
                continue;
            }

            $assignment->update(['teacher_id' => $target->id]);

            StudyGroupSubject::query()
                ->where('study_group_id', $assignment->study_group_id)
                ->where('subject_id', $assignment->subject_id)
                ->where('academic_year_id', $ay->id)
                ->update(['teacher_id' => $target->id]);

            $load[$teacherId] = $currentLoad - $jp;
            $load[$target->id] = ($load[$target->id] ?? 0) + $jp;
            $moved++;
        }

        return $moved;
    }

    /**
     * Kategori mapel berdasarkan nama (dipakai pemilihan & rebalance guru).
     */
    private function categoryFor(string $subjectName): string
    {
        $name = mb_strtolower($subjectName);

        return match (true) {
            str_contains($name, 'tahfidz'), str_contains($name, 'hafalan') => 'tahfidz',
            str_contains($name, 'hadits'), str_contains($name, 'hadist') => 'hadits',
            str_contains($name, 'arab'), str_contains($name, 'qowaid'),
            str_contains($name, 'sharaf'), str_contains($name, 'muthola'),
            str_contains($name, 'insya'), str_contains($name, 'khat') => 'arab',
            str_contains($name, 'fiqih'), str_contains($name, 'akidah'),
            str_contains($name, 'aqidah'), str_contains($name, 'adab'),
            str_contains($name, 'ski'), str_contains($name, 'qur'),
            str_contains($name, 'doa'), str_contains($name, 'tarikh'),
            str_contains($name, 'akhlak') => 'agama',
            default => 'umum',
        };
    }

    private function resolveOperationalAy(): ?AcademicYear
    {
        $byRombels = StudyGroup::query()
            ->selectRaw('academic_year_id, COUNT(*) as total')
            ->whereNotNull('academic_year_id')
            ->groupBy('academic_year_id')
            ->orderByDesc('total')
            ->pluck('academic_year_id')
            ->first();

        if ($byRombels) {
            $ay = AcademicYear::find($byRombels);
            if ($ay) {
                return $ay;
            }
        }

        return AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
    }

    /**
     * Guru untuk sebuah sekolah: roster sekolah (work unit/kepegawaian);
     * bila tidak ada, fallback seluruh GTK bergelar guru (data seed).
     */
    private function teachersFor(School $school, bool $allowFallback = false): \Illuminate\Support\Collection
    {
        $ids = SchoolGroupService::userIdsForSchool($school->id);

        $query = User::query()
            ->where(function ($q) {
                $q->whereHas('employment', fn ($e) => $e->where(function ($qq) {
                    $qq->where('jabatan', 'like', '%guru%')
                        ->orWhere('jenis_gtk', 'like', '%guru%')
                        ->orWhere('jenis_gtk', 'like', '%pendidik%');
                }));
            })
            ->orderBy('name');

        if (! empty($ids)) {
            $schoolTeachers = (clone $query)->whereIn('users.id', $ids)->get(['id', 'name']);

            if ($schoolTeachers->isNotEmpty()) {
                return $schoolTeachers;
            }
        }

        // Fallback lintas sekolah hanya bila diminta eksplisit (mode demo).
        return $allowFallback ? $query->get(['id', 'name']) : collect();
    }

    /**
     * Kelompokkan guru per kategori mapel berdasarkan jabatan.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $teachers
     * @return array<string, \Illuminate\Support\Collection<int, User>>
     */
    private function categorizeTeachers(\Illuminate\Support\Collection $teachers): array
    {
        $jabatanOf = function (User $u) {
            return mb_strtolower((string) optional($u->employment)->jabatan);
        };

        return [
            'tahfidz' => $teachers->filter(fn ($u) => str_contains($jabatanOf($u), 'tahfidz')),
            'hadits' => $teachers->filter(fn ($u) => str_contains($jabatanOf($u), 'hadits')),
            'arab' => $teachers->filter(fn ($u) => str_contains($jabatanOf($u), 'arab')),
            'agama' => $teachers->filter(fn ($u) => str_contains($jabatanOf($u), 'agama')),
            'umum' => $teachers->filter(fn ($u) => str_contains($jabatanOf($u), 'umum')
                || str_contains($jabatanOf($u), 'kelas')
                || str_contains($jabatanOf($u), 'mapel')),
            'semua' => $teachers,
        ];
    }

    /**
     * Pilih guru dengan beban terendah dari kategori yang relevan.
     *
     * @param  array<string, \Illuminate\Support\Collection<int, User>>  $byCategory
     * @param  array<string, int>  $load
     */
    private function pickTeacher(string $subjectName, array $byCategory, array $load): ?User
    {
        $category = $this->categoryFor($subjectName);

        foreach ([$category, 'semua'] as $key) {
            $candidates = $byCategory[$key] ?? collect();
            if ($candidates->isEmpty()) {
                continue;
            }

            return $candidates
                ->sortBy(fn ($t) => $load[$t->id] ?? 0)
                ->first();
        }

        return null;
    }
}
