<?php

namespace App\Http\Controllers;

use App\Models\DormitoryRoomMove;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentRoomHistoryController extends Controller
{
    public function show(Request $request, string $studentId): View
    {
        $student = Student::with(['dormitory', 'room'])->findOrFail($studentId);

        $moves = DormitoryRoomMove::with(['fromRoom', 'toRoom', 'dormitory', 'academicYear'])
            ->where('student_id', $studentId)
            ->orderBy('move_date', 'desc')
            ->get();

        $stats = [
            'total' => $moves->count(),
            'rotasi' => $moves->where('move_type', 'rotasi')->count(),
            'permintaan' => $moves->where('move_type', 'permintaan')->count(),
            'sanksi' => $moves->where('move_type', 'sanksi')->count(),
            'kondisi_kesehatan' => $moves->where('move_type', 'kondisi_kesehatan')->count(),
        ];

        return view('dormitory.students.room-history', [
            'student' => $student,
            'moves' => $moves,
            'stats' => $stats,
        ]);
    }
}
