<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TeacherAdminBook;
use Illuminate\Http\Request;

/**
 * Standar Penilaian Bahasa — KKM/KKTP tiap buku ajar bahasa.
 *
 * Membaca data yang sama dengan modul akademik (teacher_admin_books.kktp),
 * jadi angka yang tampil identik dengan yang dipakai rapor.
 */
class BahasaStandardController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYear = AcademicYear::query()->where('is_active', true)->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        $semester = $request->input('semester', $academicYear?->semester === 'genap' ? 'genap' : 'ganjil');

        $books = TeacherAdminBook::query()
            ->with(['subject:id,name,code', 'studyGroup:id,name', 'kktp'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('semester', $semester)
            ->where('is_active', true)
            ->whereHas('subject', function ($subject) {
                $subject->where(function ($q) {
                    $q->where('name', 'like', '%Arab%')
                        ->orWhere('name', 'like', '%Inggris%')
                        ->orWhere('name', 'like', '%English%')
                        ->orWhere('code', 'like', '%ARB%')
                        ->orWhere('code', 'like', '%ENG%');
                });
            })
            ->orderBy('subject_id')
            ->orderBy('study_group_id')
            ->get();

        return view('bahasa.standards.index', [
            'userId' => $userId,
            'books' => $books,
            'semester' => $semester,
            'academicYear' => $academicYear,
        ]);
    }
}
