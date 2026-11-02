<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\Student;
use Illuminate\Http\Request;

/**
 * Halaman KHUSUS data lintas asrama (administrasi boarding).
 *
 * Dipisahkan dari halaman kamar/penempatan agar data santri dari asrama lain
 * tidak tercampur pada halaman operasional asrama. Halaman ini SELALU terkunci
 * pada satu gender: asrama putra hanya L, asrama putri hanya P. Asrama campuran
 * boleh memilih satu gender pada satu waktu (tidak pernah bercampur).
 */
class DormitoryBoardingController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        // Gender yang boleh dilihat — tidak pernah lebih dari satu per tampilan.
        $allowedGenders = match ($dormitory->gender) {
            'putra' => ['L'],
            'putri' => ['P'],
            default => ['L', 'P'],
        };

        $gender = (string) $request->input('gender');
        if (! in_array($gender, $allowedGenders, true)) {
            $gender = $allowedGenders[0];
        }

        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        // Penempatan aktif per santri (termasuk asrama lain — inilah halaman lintas asrama).
        $residents = DormitoryResident::query()
            ->where('is_active', true)
            ->whereNotNull('room_id')
            ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
            ->with(['dormitory:id,name', 'room:id,name,code'])
            ->get()
            ->keyBy('student_id');

        $base = Student::query()
            ->where('school_id', $dormitory->school_id)
            ->where('status', 'active')
            ->where('gender', $gender);

        $counts = [
            'total' => (clone $base)->count(),
            'boarding' => (clone $base)->where('boarding_status', 'boarding')->count(),
            'non_boarding' => (clone $base)->where('boarding_status', 'non_boarding')->count(),
            'belum' => (clone $base)->whereNull('boarding_status')->count(),
            'berkamar' => (clone $base)->whereIn('id', $residents->keys()->all())->count(),
        ];

        $query = (clone $base)->with(['currentClassHistory.studyGroup.gradeLevel']);

        if ($request->filled('boarding_status')) {
            $status = (string) $request->input('boarding_status');
            if ($status === 'belum') {
                $query->whereNull('boarding_status');
            } elseif (in_array($status, ['boarding', 'non_boarding'], true)) {
                $query->where('boarding_status', $status);
            }
        }

        if ($request->filled('q')) {
            $needle = $request->input('q');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$needle}%")
                ->orWhere('nisn', 'like', "%{$needle}%")
                ->orWhere('nis', 'like', "%{$needle}%"));
        }

        $students = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('dormitory.students.boarding', [
            'userId' => $userId,
            'dormitory' => $dormitory,
            'students' => $students,
            'residents' => $residents,
            'counts' => $counts,
            'gender' => $gender,
            'allowedGenders' => $allowedGenders,
        ]);
    }
}
