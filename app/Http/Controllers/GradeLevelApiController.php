<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;

class GradeLevelApiController extends Controller
{
    public function bySchool(string $schoolId)
    {
        $gradeLevels = GradeLevel::where('school_id', $schoolId)
            ->orderBy('level')
            ->get(['id', 'name', 'code', 'level']);

        return response()->json(['success' => true, 'data' => $gradeLevels]);
    }

    public function byAcademicYear(Request $request, string $academicYearId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $isGlobalView = $request->attributes->get('isGlobalView', false);
        $schoolId = $request->get('school_id');

        // Super Admin global tanpa scoped school → tampilkan semua rombel
        // Super Admin scoped (sa_school_id) atau user biasa → filter sesuai sekolah
        $shouldFilterBySchool = !($isGlobalView && !$schoolContextId);

        $query = StudyGroup::with(['gradeLevel'])
            ->where('academic_year_id', $academicYearId);

        if ($shouldFilterBySchool && $schoolContextId) {
            $query->where('school_id', $schoolContextId);
        } elseif ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $studyGroups = $query->orderBy('name')->get(['id', 'name', 'grade_level_id']);

        return response()->json([
            'success' => true,
            'study_groups' => $studyGroups,
        ]);
    }

    public function teachersBySchool(string $schoolId)
    {
        $teachers = User::whereHas('employment', fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $teachers]);
    }
}
