<?php

namespace App\Http\Controllers;

use App\Models\DormitoryViolation;
use App\Models\Student;
use App\Models\ViolationPoint;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentViolationController extends Controller
{
    public function index(Request $request, string $studentId): View
    {
        $student = Student::with(['dormitory', 'room'])->findOrFail($studentId);

        $violations = DormitoryViolation::with(['room', 'dormitory'])
            ->where('student_id', $studentId)
            ->orderBy('violation_date', 'desc')
            ->get();

        $points = ViolationPoint::where('student_id', $studentId)->get();
        $totalPoints = $points->sum('points');

        $stats = [
            'total' => $violations->count(),
            'ringan' => $violations->where('violation_category', 'ringan')->count(),
            'sedang' => $violations->where('violation_category', 'sedang')->count(),
            'berat' => $violations->where('violation_category', 'berat')->count(),
            'total_points' => $totalPoints,
        ];

        return view('dormitory.students.violations', [
            'student' => $student,
            'violations' => $violations,
            'stats' => $stats,
        ]);
    }
}