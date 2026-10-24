<?php

namespace App\Services;

use App\Http\Controllers\NilaiKelasController;
use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\RaportRegistration;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Hitung kelas yang masih perlu perhatian pada modul nilai:
 * leger belum lengkap (STS/SAS) atau rapor belum dipublikasikan.
 *
 * Mapel Liqo dikecualikan karena merupakan kegiatan rutin.
 */
class NilaiReminderService
{
    public function countForUser(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $academicYear = AcademicYear::where('is_active', true)->first();
        if (! $academicYear) {
            return 0;
        }

        $schoolContextId = request()->attributes->get('schoolContextId');
        $cacheKey = 'nilai-reminder:'.$user->id.':'.($schoolContextId ?: 'all').':'.$academicYear->id;

        return (int) Cache::remember($cacheKey, now()->addMinute(), function () use ($user, $academicYear, $schoolContextId) {
            $access = NilaiKelasController::legerAccessFor($user->id);
            $groups = StudyGroup::query()
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->when($schoolContextId, fn ($query) => $query->where('school_id', $schoolContextId))
                ->when($access !== 'full', fn ($query) => $query->where('homeroom_teacher_id', $user->id))
                ->get();

            $semester = $academicYear->semester ?: 'ganjil';
            $pending = 0;

            foreach ($groups as $group) {
                $studentIds = StudentClassHistory::query()
                    ->where('study_group_id', $group->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('is_active', true)
                    ->pluck('student_id');

                $books = TeacherAdminBook::with('subject')
                    ->where('study_group_id', $group->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('semester', $semester)
                    ->where('is_active', true)
                    ->get()
                    ->reject(fn ($book) => preg_match(
                        '/liqo/i',
                        (string) ($book->subject?->name ?? '').' '.(string) ($book->subject?->code ?? '')
                    ));

                if ($studentIds->isEmpty() || $books->isEmpty()) {
                    $pending++;
                    continue;
                }

                $nilaiRows = NilaiSumatif::query()
                    ->whereIn('admin_book_id', $books->pluck('id'))
                    ->whereIn('student_id', $studentIds)
                    ->where('semester', $semester)
                    ->get()
                    ->keyBy(fn ($row) => $row->admin_book_id.'|'.$row->student_id);

                $incomplete = false;

                foreach ($books as $book) {
                    foreach ($studentIds as $studentId) {
                        $row = $nilaiRows->get($book->id.'|'.$studentId);

                        $sts = $row?->sts;
                        $sas = $row?->nr_final ?? $row?->sas;

                        if ($sts === null || $sas === null) {
                            $incomplete = true;
                            break 2;
                        }
                    }
                }

                if ($incomplete) {
                    $pending++;
                    continue;
                }

                $published = RaportRegistration::query()
                    ->where('study_group_id', $group->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('semester', $semester)
                    ->where('is_published', true)
                    ->whereIn('student_id', $studentIds)
                    ->count();

                if ($published < $studentIds->count()) {
                    $pending++;
                }
            }

            return $pending;
        });
    }
}
