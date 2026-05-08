<?php

namespace App\Http\Controllers;

use App\Models\StudentMutation;
use App\Models\Student;
use App\Models\School;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentMutationController extends Controller
{
    public function index(Request $request)
    {
        $userId = request()->route('userId');

        $query = StudentMutation::with(['student', 'school', 'requestedBy'])
            ->orderByDesc('created_at');

        // Scoped filter: only mutations belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('student_name', 'like', "%{$q}%")
                ->orWhere('student_nisn', 'like', "%{$q}%")
                ->orWhere('letter_number', 'like', "%{$q}%")
            );
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $mutations = $query->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view('students.mutasi.index', compact('mutations', 'schools', 'userId'));
    }

    public function create(Request $request)
    {
        $userId = request()->route('userId');
        $type = $request->get('type', 'keluar');
        $schoolId = $request->attributes->get('schoolContextId');

        // Scoped: only allow the user's school
        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
        } else {
            $schools = School::orderBy('name')->get();
        }

        // Pre-fill student if pre-selected
        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::find($request->student_id);
        }

        return view('students.mutasi.create', compact('type', 'schools', 'student', 'userId'));
    }

    public function store(Request $request)
    {
        $userId = request()->route('userId');
        $type = $request->type;
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'type'        => 'required|in:keluar,masuk',
            'student_id'  => 'required|exists:students,id',
            'student_name' => 'required|string|max:255',
            'status'      => 'in:draft,submitted',
            // Letter
            'letter_number'    => 'nullable|string|max:50',
            'letter_attachment' => 'nullable|string|max:20',
            'institution_name'  => 'nullable|string|max:255',
            'institution_address' => 'nullable|string|max:500',
            'institution_phone'   => 'nullable|string|max:50',
            'institution_email'   => 'nullable|email|max:100',
            'head_name'    => 'nullable|string|max:100',
            'head_title'   => 'nullable|string|max:100',
            'head_nip'     => 'nullable|string|max:30',
            'established_city'  => 'nullable|string|max:100',
            'established_date'  => 'nullable|date',
            // Student
            'student_nisn'     => 'nullable|string|max:20',
            'student_nis'      => 'nullable|string|max:20',
            'student_gender'   => 'nullable|in:L,P',
            'student_birth_date'  => 'nullable|date',
            'student_birth_place'  => 'nullable|string|max:100',
            'student_address'  => 'nullable|string|max:500',
            'student_previous_school' => 'nullable|string|max:255',
            'student_current_class'   => 'nullable|string|max:50',
            // Parent
            'parent_name'     => 'nullable|string|max:255',
            'parent_occupation' => 'nullable|string|max:100',
            'parent_address'   => 'nullable|string|max:500',
            'parent_phone'     => 'nullable|string|max:30',
            // Destination / Origin
            'destination_school_name'    => 'nullable|string|max:255',
            'destination_school_address' => 'nullable|string|max:500',
            'destination_school_city'    => 'nullable|string|max:100',
            'origin_school_name'    => 'nullable|string|max:255',
            'origin_school_address' => 'nullable|string|max:500',
            'origin_school_city'    => 'nullable|string|max:100',
            // Reason
            'reason'   => 'nullable|string',
            'notes'    => 'nullable|string',
            // Recommendation (masuk)
            'recommendation_number' => 'nullable|string|max:50',
            'recommendation_year'   => 'nullable|string|max:10',
        ];

        $validated = $request->validate($rules);

        // Scoped: enforce school_id from context
        if ($schoolId) {
            $validated['school_id'] = $schoolId;
        }

        // Update student status based on mutation type
        $student = Student::find($validated['student_id']);
        if ($type === 'keluar') {
            $student->update(['status' => 'transfer_out']);
        } elseif ($type === 'masuk') {
            $student->update(['status' => 'transfer_in']);
        }

        $mutation = StudentMutation::create([
            ...$validated,
            'requested_by' => Auth::id(),
            'status'       => $request->boolean('submit_now') ? 'submitted' : 'draft',
        ]);

        return redirect()->route('user.students.mutasi.show', ['userId' => $userId, 'mutation' => $mutation->id])
            ->with('success', 'Mutasi berhasil disimpan.');
    }

    public function show(string $mutationUuid)
    {
        $userId = request()->route('userId');
        $mutation = StudentMutation::with(['student', 'school', 'requestedBy', 'approvedBy'])
            ->findOrFail($mutationUuid);
        return view('students.mutasi.show', compact('mutation', 'userId'));
    }

    public function submit(string $mutationUuid)
    {
        $userId = request()->route('userId');
        $mutation = StudentMutation::findOrFail($mutationUuid);
        $mutation->update(['status' => 'submitted']);
        return back()->with('success', 'Mutasi berhasil diajukan.');
    }

    public function approve(Request $request, string $mutationUuid)
    {
        $userId = request()->route('userId');
        $mutation = StudentMutation::with('student')->findOrFail($mutationUuid);
        $mutation->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        return back()->with('success', 'Mutasi berhasil disetujui.');
    }

    public function reject(Request $request, string $mutationUuid)
    {
        $userId = request()->route('userId');
        $mutation = StudentMutation::findOrFail($mutationUuid);
        $mutation->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);
        // Revert student status
        $student = $mutation->student;
        if ($student && in_array($student->status, ['transfer_out', 'transfer_in'])) {
            $student->update(['status' => 'active']);
        }
        return back()->with('success', 'Mutasi ditolak.');
    }

    public function destroy(string $mutationUuid)
    {
        $userId = request()->route('userId');
        $mutation = StudentMutation::findOrFail($mutationUuid);
        // Only allow delete for draft
        if ($mutation->status !== 'draft') {
            return back()->with('error', 'Mutasi yang sudah diajukan tidak bisa dihapus.');
        }
        // Revert student status if was changed
        $student = $mutation->student;
        if ($student && in_array($student->status, ['transfer_out', 'transfer_in'])) {
            $student->update(['status' => 'active']);
        }
        $mutation->delete();
        return redirect()->route('user.students.mutasi.index', ['userId' => $userId])
            ->with('success', 'Mutasi berhasil dihapus.');
    }

    public function printKeluar(string $mutationUuid)
    {
        $userId = request()->route('userId');
        $mutation = StudentMutation::with(['student', 'school'])->findOrFail($mutationUuid);

        if ($mutation->type !== 'keluar') {
            abort(400, 'Bukan mutasi keluar.');
        }

        return view('students.mutasi.print.keluar', compact('mutation', 'userId'));
    }

    public function printMasuk(string $mutationUuid)
    {
        $userId = request()->route('userId');
        $mutation = StudentMutation::with(['student', 'school'])->findOrFail($mutationUuid);

        if ($mutation->type !== 'masuk') {
            abort(400, 'Bukan mutasi masuk.');
        }

        return view('students.mutasi.print.masuk', compact('mutation', 'userId'));
    }

    public function findStudent(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $students = Student::where('status', 'active')
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