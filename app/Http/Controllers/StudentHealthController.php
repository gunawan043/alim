<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentHealthPermit;
use App\Models\StudentHealthRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentHealthController extends Controller
{
    public function show(Request $request, string $studentId): View
    {
        $student = Student::with(['dormitory', 'room'])->findOrFail($studentId);

        $record = StudentHealthRecord::where('student_id', $studentId)->first();

        $permits = StudentHealthPermit::where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $stats = [
            'bmi_normal' => $record && $record->bmi >= 18.5 && $record->bmi < 25,
            'permits_total' => $permits->count(),
            'permits_sakit' => $permits->whereIn('permit_type', ['sakit_ringan', 'sakit_sedang'])->count(),
            'permits_berat' => $permits->whereIn('permit_type', ['sakit_berat', 'rawat_inap'])->count(),
        ];

        return view('dormitory.students.health', [
            'student' => $student,
            'record' => $record,
            'permits' => $permits,
            'stats' => $stats,
        ]);
    }
}
