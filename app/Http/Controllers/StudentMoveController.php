<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentLifecycleAudit;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentMoveController extends Controller
{
    /**
     * Display the student move page.
     *
     * GET /{userId}/student-move?study_group_id=xxx
     */
    public function index(Request $request, string $userId)
    {
        // Source study group (where students are currently)
        $sourceStudyGroup = null;
        if ($request->filled('study_group_id')) {
            $sourceStudyGroup = StudyGroup::with(['gradeLevel', 'school', 'homeroomTeacher'])
                ->find($request->study_group_id);
        }

        $schoolId = $request->attributes->get('schoolContextId');
        if ($sourceStudyGroup && $schoolId && $sourceStudyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        // Get available destination rombel options
        // Must be: same grade level, same academic year, different rombel, same school
        $availableDestinations = collect();
        if ($sourceStudyGroup) {
            $availableDestinations = StudyGroup::with(['gradeLevel', 'school'])
                ->where('id', '!=', $sourceStudyGroup->id)
                ->where('school_id', $sourceStudyGroup->school_id)
                ->where('academic_year_id', $sourceStudyGroup->academic_year_id)
                ->where('grade_level_id', $sourceStudyGroup->grade_level_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($sg) use ($sourceStudyGroup) {
                    $sg->studentCount = StudentClassHistory::where('study_group_id', $sg->id)
                        ->where('academic_year_id', $sourceStudyGroup->academic_year_id)
                        ->where('is_active', true)
                        ->count();

                    return $sg;
                });
        }

        // Students currently in the source rombel
        $students = collect([]);
        if ($sourceStudyGroup) {
            $studentIds = StudentClassHistory::where('study_group_id', $sourceStudyGroup->id)
                ->where('academic_year_id', $sourceStudyGroup->academic_year_id)
                ->where('is_active', true)
                ->pluck('student_id');

            $students = Student::whereIn('id', $studentIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date']);
        }

        return view('student-move.index', compact(
            'userId', 'sourceStudyGroup', 'students',
            'availableDestinations',
        ));
    }

    /**
     * Process the student move (pindahkan Santri ke rombel lain, tingkat sama).
     *
     * POST /{userId}/student-move
     */
    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'source_study_group_id' => 'required|exists:study_groups,id',
            'destination_study_group_id' => 'required|exists:study_groups,id|different:source_study_group_id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'move_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $sourceSg = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($validated['source_study_group_id']);
        $destSg = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($validated['destination_study_group_id']);

        // Security: school scoping
        if ($schoolId && ($sourceSg->school_id !== $schoolId || $destSg->school_id !== $schoolId)) {
            abort(403, 'Akses ditolak.');
        }

        // Validation: must be same academic year
        if ($sourceSg->academic_year_id !== $destSg->academic_year_id) {
            return back()->withInput()->with('error', 'Rombel asal dan tujuan harus berada pada tahun ajaran yang sama.');
        }

        // Validation: must be same grade level
        if ($sourceSg->grade_level_id !== $destSg->grade_level_id) {
            return back()->withInput()->with('error', 'Pindahan hanya dapat dilakukan antar rombel dengan tingkat yang SAMA. Gunakan menu Kenaikan Kelas untuk memindahkan ke tingkat berbeda.');
        }

        // Validation: cannot move to same rombel
        if ($sourceSg->id === $destSg->id) {
            return back()->withInput()->with('error', 'Rombel asal dan tujuan tidak boleh sama.');
        }

        $ayId = $sourceSg->academic_year_id;
        $studentIds = array_values(array_unique($validated['student_ids']));

        // Pastikan SEMUA santri terpilih benar-benar aktif di rombel asal (gagal total, bukan skip diam-diam).
        $validHistories = StudentClassHistory::query()
            ->whereIn('student_id', $studentIds)
            ->where('study_group_id', $sourceSg->id)
            ->where('academic_year_id', $ayId)
            ->where('is_active', true)
            ->get()
            ->keyBy('student_id');

        $activeStudentIds = Student::whereIn('id', $studentIds)
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        $invalid = collect($studentIds)->filter(
            fn ($id) => ! $validHistories->has($id) || ! in_array($id, $activeStudentIds, true)
        );

        if ($invalid->isNotEmpty()) {
            return back()->withInput()->with(
                'error',
                $invalid->count().' santri terpilih tidak ditemukan sebagai anggota aktif rombel asal. Muat ulang halaman lalu pilih ulang.'
            );
        }

        // Capacity check: destination rombel (tahun ajaran yang sama)
        $currentDestCount = StudentClassHistory::where('study_group_id', $destSg->id)
            ->where('academic_year_id', $ayId)
            ->where('is_active', true)
            ->count();

        $movingCount = count($studentIds);
        $availableSlots = max(0, $destSg->capacity - $currentDestCount);

        if ($movingCount > $availableSlots) {
            return back()->withInput()->with(
                'error',
                "Rombel tujuan hanya memiliki {$availableSlots} slot tersisa. Anda mencoba memindahkan {$movingCount} santri. Kurangi jumlah yang dipilih atau pilih rombel lain."
            );
        }

        $moveDate = $validated['move_date'];
        $notes = $validated['notes'] ?? 'Pindahan rombel tingkat sama';
        $actorId = $request->user()?->id;

        DB::transaction(function () use ($sourceSg, $destSg, $ayId, $moveDate, $notes, $validHistories, $studentIds, $currentDestCount, $actorId) {
            $nextNumber = $currentDestCount;

            foreach ($studentIds as $studentId) {
                $history = $validHistories->get($studentId);
                $nextNumber++;

                // Pindahkan baris history yang SAMA (unique student+academic_year),
                // bukan menonaktifkan lalu membuat baris baru.
                $history->update([
                    'study_group_id' => $destSg->id,
                    'join_date' => $moveDate,
                    'attendance_number' => $nextNumber,
                    'notes' => trim(($history->notes ? $history->notes.' | ' : '').$notes),
                ]);

                StudentLifecycleAudit::create([
                    'event' => 'student.moved_rombel',
                    'student_id' => $studentId,
                    'school_id' => $sourceSg->school_id,
                    'actor_id' => $actorId,
                    'payload' => [
                        'from_sg' => $sourceSg->id,
                        'to_sg' => $destSg->id,
                        'academic_year_id' => $ayId,
                        'move_date' => $moveDate,
                    ],
                    'occurred_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('user.students.index', [
                'userId' => $userId,
                'study_group_id' => $destSg->id,
            ])
            ->with('success', "Pindahkan Santri selesai. {$movingCount} santri berhasil dipindahkan ke {$destSg->full_name}.");
    }
}
