<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $userId = request()->route('userId');
        $query = Student::with(['school', 'classHistories.studyGroup']);

        // Scoped filter: only students belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('study_group_id')) {
            $query->whereHas('classHistories', fn($q) => $q
                ->where('study_group_id', $request->study_group_id)
                ->where('is_active', true)
            );
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
                ->orWhere('nis', 'like', "%{$q}%")
                ->orWhere('nik', 'like', "%{$q}%")
            );
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('name')->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get();

        // ── Stats (reusable base query with all active filters except pagination) ──
        $baseQuery = clone $query;

        // Get the raw count from the query (before clone messiness, rebuild from same filters)
        $isFilteredByClass = $request->filled('study_group_id');
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        $statsQuery = Student::query();
        if ($schoolId) {
            $statsQuery->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $statsQuery->where('school_id', $request->school_id);
        }
        if ($request->filled('study_group_id')) {
            $statsQuery->whereHas('classHistories', fn($q) => $q
                ->where('study_group_id', $request->study_group_id)
                ->where('is_active', true)
            );
        }
        if ($request->filled('gender')) {
            $statsQuery->where('gender', $request->gender);
        }
        if ($request->filled('status')) {
            $statsQuery->where('status', $request->status);
        }

        $totalAll = (clone $statsQuery)->count();
        $totalActive = (clone $statsQuery)->where('status', 'active')->count();

        // Stats by rombel (for massal view)
        $byRombel = [];
        $distribusiPerTingkat = [];
        if (!$request->filled('study_group_id')) {
            $assignedInAY = StudentClassHistory::where('is_active', true)
                ->pluck('student_id');

            $unassignedCount = (clone $statsQuery)
                ->whereNotIn('id', $assignedInAY)
                ->where('status', 'active')
                ->count();

            $inRombelCount = (clone $statsQuery)
                ->whereIn('id', $assignedInAY)
                ->where('status', 'active')
                ->count();

            $byRombel = [
                'unassigned' => $unassignedCount,
                'in_rombel' => $inRombelCount,
            ];

            // Distribusi per tingkat (massal view only)
            $distribusiPerTingkat = StudentClassHistory::selectRaw('grade_levels.name as grade_name, COUNT(student_class_histories.id) as total')
                ->join('study_groups', 'study_groups.id', '=', 'student_class_histories.study_group_id')
                ->join('grade_levels', 'grade_levels.id', '=', 'study_groups.grade_level_id')
                ->where('student_class_histories.is_active', true)
                ->when($schoolId, fn($q) => $q->where('study_groups.school_id', $schoolId))
                ->groupBy('grade_levels.name')
                ->orderByRaw("CAST(grade_levels.name AS INTEGER) ASC")
                ->get();
        }

        // Per-kelas stats (for rombel-filtered view)
        $studyGroup = null;
        $capacity = null;
        $inClass = null;
        if ($request->filled('study_group_id')) {
            $studyGroup = StudyGroup::with(['gradeLevel', 'homeroomTeacher', 'school'])->find($request->study_group_id);
            $capacity = $studyGroup?->capacity ?? 0;
            $inClass = (clone $statsQuery)->whereHas('classHistories',
                fn($q) => $q->where('study_group_id', $request->study_group_id)
                           ->where('is_active', true)
            )->count();
        }

        return view('students.index', compact(
            'students', 'schools', 'userId',
            'totalAll', 'totalActive',
            'byRombel', 'studyGroup', 'capacity', 'inClass',
            'distribusiPerTingkat', 'isFilteredByClass',
        ));
    }

    public function create(Request $request)
    {
        $userId = request()->route('userId');
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
        } else {
            $schools = School::orderBy('name')->get();
        }
        return view('students.create', compact('schools', 'userId'));
    }

    public function store(Request $request)
    {
        // Scoped: enforce school_id from context
        $schoolId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'school_id'  => $schoolId ? 'sometimes|exists:schools,id' : 'required|exists:schools,id',
            'nisn'       => 'required|string|max:20|unique:students,nisn',
            'nis'        => 'nullable|string|max:20',
            'nik'        => 'nullable|string|max:30|unique:students,nik',
            'no_kk'      => 'nullable|string|max:30',
            'name'       => 'required|string|max:255',
            'gender'     => 'required|in:L,P',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion'   => 'nullable|string|max:50',
            'special_needs' => 'nullable|in:tidak,fisik,intelektual,mental,sosial',
            // Alamat
            'address'    => 'nullable|string',
            'rt'        => 'nullable|string|max:5',
            'rw'        => 'nullable|string|max:5',
            'hamlet'    => 'nullable|string|max:100',
            'province_code' => 'nullable|string|max:2',
            'city_code'  => 'nullable|string|max:4',
            'district_code' => 'nullable|string|max:7',
            'village_code' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            // Kontak
            'phone'      => 'nullable|string|max:20',
            'mobile_phone' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100',
            // Tempat tinggal
            'residence_type' => 'nullable|in:milik_orangtua,sewa,asrama,panti,lainnya',
            'transportation' => 'nullable|in:jalan_kaki,sepeda,motor,mobil,angkutan_umum,antar_jemput',
            'distance_to_school' => 'nullable|numeric|min:0',
            // Kesehatan
            'height'     => 'nullable|integer|min:0',
            'weight'     => 'nullable|integer|min:0',
            'head_circumference' => 'nullable|integer|min:0',
            'sibling_count' => 'nullable|integer|min:0',
            // Ayah
            'father_name' => 'nullable|string|max:255',
            'father_birth_year' => 'nullable|integer|min:1900|max:2030',
            'father_education' => 'nullable|string|max:50',
            'father_occupation' => 'nullable|string|max:100',
            'father_income' => 'nullable|numeric|min:0',
            'father_nik'  => 'nullable|string|max:30',
            // Ibu
            'mother_name' => 'nullable|string|max:255',
            'mother_birth_year' => 'nullable|integer|min:1900|max:2030',
            'mother_education' => 'nullable|string|max:50',
            'mother_occupation' => 'nullable|string|max:100',
            'mother_income' => 'nullable|numeric|min:0',
            'mother_nik'  => 'nullable|string|max:30',
            // Wali
            'guardian_name' => 'nullable|string|max:255',
            'guardian_birth_year' => 'nullable|integer|min:1900|max:2030',
            'guardian_education' => 'nullable|string|max:50',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_income' => 'nullable|numeric|min:0',
            'guardian_nik' => 'nullable|string|max:30',
            // Pendaftaran
            'child_number' => 'nullable|integer|min:0',
            'previous_school' => 'nullable|string|max:255',
            'entry_date'   => 'nullable|date',
            'entry_grade_level' => 'nullable|integer|min:1|max:15',
            'skhun'  => 'nullable|string|max:50',
            'ujian_national_number' => 'nullable|string|max:50',
            'certificate_number' => 'nullable|string|max:50',
            'birth_certificate_number' => 'nullable|string|max:50',
            // PIP/KIP/KPS
            'is_kps_receiver' => 'boolean',
            'kps_number' => 'nullable|string|max:50',
            'is_kip_receiver' => 'boolean',
            'kip_number' => 'nullable|string|max:50',
            'kip_name' => 'nullable|string|max:255',
            'kks_number' => 'nullable|string|max:50',
            'is_pip_eligible' => 'boolean',
            'pip_reason' => 'nullable|string',
            // Bank
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            // Status
            'status'    => 'nullable|in:active,inactive,graduate,dropped,transfer',
            'graduation_year' => 'nullable|integer|min:1900|max:2100',
            'graduation_date' => 'nullable|date',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo_path')) {
            $path = $request->file('photo_path')->store('students/photos', 'public');
            $data['photo_path'] = $path;
        }

        $student = Student::create($data);

        return redirect()->route('user.students.show', ['userId' => $request->route('userId'), 'santriUuid' => $student->id])
            ->with('success', 'Data siswa berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $santriUuid)
    {
        \Log::info("STUDENT_SHOW_CONTROLLER: uuid={$santriUuid}");
        $userId = $request->route('userId');
        $student = Student::withoutGlobalScope('school_context')
            ->with([
                'school', 'province', 'city', 'district', 'village',
                'classHistories.studyGroup.gradeLevel',
                'classHistories.academicYear',
                'achievements.academicYear',
                'achievements.coach',
            ])->findOrFail($santriUuid);

        return view('students.show', compact('student', 'userId'));
    }

    public function edit(Request $request, string $userId, string $santriUuid)
    {
        \Log::info("STUDENT_EDIT_CONTROLLER: uuid={$santriUuid}");
        $userId = $request->route('userId');
        $student = Student::withoutGlobalScope('school_context')->findOrFail($santriUuid);
        $schools = School::orderBy('name')->get();
        return view('students.edit', compact('student', 'schools', 'userId'));
    }

    public function update(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::withoutGlobalScope('school_context')->findOrFail($santriUuid);

        $data = $request->validate([
            'school_id'  => 'required|exists:schools,id',
            'nisn'       => 'required|string|max:20|unique:students,nisn,' . $santriUuid,
            'nis'        => 'nullable|string|max:20',
            'nik'        => 'nullable|string|max:30|unique:students,nik,' . $santriUuid,
            'no_kk'      => 'nullable|string|max:30',
            'name'       => 'required|string|max:255',
            'gender'     => 'required|in:L,P',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion'   => 'nullable|string|max:50',
            'special_needs' => 'nullable|in:tidak,fisik,intelektual,mental,sosial',
            'address'    => 'nullable|string',
            'rt'        => 'nullable|string|max:5',
            'rw'        => 'nullable|string|max:5',
            'hamlet'    => 'nullable|string|max:100',
            'province_code' => 'nullable|string|max:2',
            'city_code'  => 'nullable|string|max:4',
            'district_code' => 'nullable|string|max:7',
            'village_code' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            'phone'      => 'nullable|string|max:20',
            'mobile_phone' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100',
            'residence_type' => 'nullable|in:milik_orangtua,sewa,asrama,panti,lainnya',
            'transportation' => 'nullable|in:jalan_kaki,sepeda,motor,mobil,angkutan_umum,antar_jemput',
            'distance_to_school' => 'nullable|numeric|min:0',
            'height'     => 'nullable|integer|min:0',
            'weight'     => 'nullable|integer|min:0',
            'head_circumference' => 'nullable|integer|min:0',
            'sibling_count' => 'nullable|integer|min:0',
            'father_name' => 'nullable|string|max:255',
            'father_birth_year' => 'nullable|integer|min:1900|max:2030',
            'father_education' => 'nullable|string|max:50',
            'father_occupation' => 'nullable|string|max:100',
            'father_income' => 'nullable|numeric|min:0',
            'father_nik'  => 'nullable|string|max:30',
            'mother_name' => 'nullable|string|max:255',
            'mother_birth_year' => 'nullable|integer|min:1900|max:2030',
            'mother_education' => 'nullable|string|max:50',
            'mother_occupation' => 'nullable|string|max:100',
            'mother_income' => 'nullable|numeric|min:0',
            'mother_nik'  => 'nullable|string|max:30',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_birth_year' => 'nullable|integer|min:1900|max:2030',
            'guardian_education' => 'nullable|string|max:50',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_income' => 'nullable|numeric|min:0',
            'guardian_nik' => 'nullable|string|max:30',
            'child_number' => 'nullable|integer|min:0',
            'previous_school' => 'nullable|string|max:255',
            'entry_date'   => 'nullable|date',
            'entry_grade_level' => 'nullable|integer|min:1|max:15',
            'skhun'  => 'nullable|string|max:50',
            'ujian_national_number' => 'nullable|string|max:50',
            'certificate_number' => 'nullable|string|max:50',
            'birth_certificate_number' => 'nullable|string|max:50',
            'is_kps_receiver' => 'boolean',
            'kps_number' => 'nullable|string|max:50',
            'is_kip_receiver' => 'boolean',
            'kip_number' => 'nullable|string|max:50',
            'kip_name' => 'nullable|string|max:255',
            'kks_number' => 'nullable|string|max:50',
            'is_pip_eligible' => 'boolean',
            'pip_reason' => 'nullable|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'status'    => 'nullable|in:active,inactive,graduate,dropped,transfer',
            'graduation_year' => 'nullable|integer|min:1900|max:2100',
            'graduation_date' => 'nullable|date',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo_path')) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $path = $request->file('photo_path')->store('students/photos', 'public');
            $data['photo_path'] = $path;
        }
        // Remove photo
        if ($request->boolean('remove_photo')) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $data['photo_path'] = null;
        }

        $student->update($data);
        return redirect()->route('user.students.show', ['userId' => $request->route('userId'), 'santriUuid' => $student->id])
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Request $request, string $santriUuid)
    {
        $userId = $request->route('userId');
        $student = Student::withoutGlobalScope('school_context')->findOrFail($santriUuid);
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }
        $student->delete();
        return redirect()->route('user.students.index', ['userId' => $userId])
            ->with('success', 'Siswa berhasil dihapus.');
    }

    public function findStudent(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $students = Student::withoutGlobalScope('school_context')->where('status', 'active')
            ->where(fn($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
            )
            ->limit(20)
            ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date', 'address']);

        return response()->json($students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'nisn'        => $s->nisn,
            'gender'      => $s->gender,
            'gender_text' => $s->gender_text,
            'birth_place' => $s->birth_place,
            'birth_date'  => $s->birth_date?->format('d/m/Y'),
            'address'     => $s->address,
        ]));
    }
}
