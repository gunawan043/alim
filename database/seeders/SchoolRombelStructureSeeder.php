<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data wajib rombel per jenjang + tautan mapel ke setiap rombel:
 *  - SD       : 6 tingkat × 3 rombel (A–C)
 *  - SMP      : 3 tingkat × 5 rombel (A–E)
 *  - SMA / MA : 3 tingkat × 3 rombel (A–C)
 *
 * Setiap rombel langsung dihubungkan ke seluruh mapel tingkatnya
 * (grade_level_subjects → study_group_subjects), siap dipakai plotting
 * penugasan, jadwal, dan buku nilai.
 *
 * Idempoten — aman dijalankan berulang.
 */
class SchoolRombelStructureSeeder extends Seeder
{
    public function run(): void
    {
        // TA operasional: TA dengan rombel/penugasan terbanyak → TA aktif → terbaru.
        $ay = $this->resolveAcademicYear();

        if (! $ay) {
            $this->command?->warn('Tidak ada tahun ajaran — seeder rombel dilewati.');

            return;
        }

        $createdRombels = 0;
        $existingRombels = 0;
        $createdLinks = 0;

        foreach (School::orderBy('name')->get() as $school) {
            $grades = GradeLevel::where('school_id', $school->id)->orderBy('level')->get();

            foreach ($grades as $grade) {
                $letters = $this->lettersForLevel((int) $grade->level);
                if (empty($letters)) {
                    continue;
                }

                $glsList = GradeLevelSubject::where('grade_level_id', $grade->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($letters as $letter) {
                    // Nama & kode rombel cukup A/B/C — tingkat sudah dibedakan grade_level.
                    $adopted = StudyGroup::query()
                        ->where('school_id', $school->id)
                        ->where('academic_year_id', $ay->id)
                        ->where('grade_level_id', $grade->id)
                        ->where('name', $letter)
                        ->first();

                    $prefixed = StudyGroup::query()
                        ->where('school_id', $school->id)
                        ->where('academic_year_id', $ay->id)
                        ->where('grade_level_id', $grade->id)
                        ->where('name', $grade->level.$letter)
                        ->first();

                    // Nama A/B/C sudah dipakai rombel lama (mis. "A" uji coba) → bersihkan
                    // rombel lama tsb agar penamaan seragam, lalu pakai rombel utama.
                    if ($adopted && $prefixed && $adopted->id !== $prefixed->id) {
                        app(\App\Services\StudyGroupService::class)->deleteSafely($adopted);
                        $adopted = null;
                    }

                    if ($adopted) {
                        $rombel = $adopted;
                        $existingRombels++;
                    } elseif ($prefixed) {
                        $prefixed->forceFill(['name' => $letter, 'code' => $letter])->save();
                        $rombel = $prefixed;
                        $existingRombels++;
                    } else {
                        $rombel = StudyGroup::create([
                            'id' => (string) Str::uuid(),
                            'school_id' => $school->id,
                            'academic_year_id' => $ay->id,
                            'grade_level_id' => $grade->id,
                            'name' => $letter,
                            'code' => $letter,
                            'capacity' => 32,
                            'curriculum_type' => 'merdeka',
                            'shift' => 'pagi',
                            'is_active' => true,
                        ]);
                        $createdRombels++;
                    }

                    foreach ($glsList as $gls) {
                        $exists = StudyGroupSubject::where('study_group_id', $rombel->id)
                            ->where('subject_id', $gls->subject_id)
                            ->where('academic_year_id', $ay->id)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        StudyGroupSubject::create([
                            'id' => (string) Str::uuid(),
                            'school_id' => $school->id,
                            'academic_year_id' => $ay->id,
                            'study_group_id' => $rombel->id,
                            'subject_id' => $gls->subject_id,
                            'teacher_id' => null,
                            'weekly_hours' => (float) ($gls->allocation_hours ?: 2),
                            'is_active' => true,
                        ]);

                        $createdLinks++;
                    }
                }
            }
        }

        $this->command?->info(
            "Rombel [TA {$ay->name}]: {$createdRombels} baru, {$existingRombels} sudah ada; "
            ."{$createdLinks} tautan mapel–rombel dibuat."
        );
    }

    private function resolveAcademicYear(): ?AcademicYear
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
     * Jumlah rombel per jenjang tingkat.
     *
     * @return array<int, string>
     */
    private function lettersForLevel(int $level): array
    {
        return match (true) {
            $level >= 1 && $level <= 6 => ['A', 'B', 'C'],            // SD: 3 rombel
            $level >= 7 && $level <= 9 => ['A', 'B', 'C', 'D', 'E'],  // SMP: 5 rombel
            $level >= 10 && $level <= 12 => ['A', 'B', 'C'],          // SMA/MA: 3 rombel
            default => [],
        };
    }
}
