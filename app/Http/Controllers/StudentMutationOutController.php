<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentMutationOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentMutationOutController extends Controller
{
    public function index(Request $request)
    {
        $userId = request()->route('userId');

        $query = StudentMutationOut::with(['student', 'school', 'requestedBy'])
            ->orderByDesc('created_at');

        // Scoped user: only their school
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('student_name', 'like', "%{$q}%")
                ->orWhere('student_nisn', 'like', "%{$q}%")
                ->orWhere('letter_number', 'like', "%{$q}%")
            );
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('out_type')) {
            $query->where('out_type', $request->out_type);
        }

        // Determine view and out_type filter based on route
        $routeName = $request->route()->getName();
        $view = match (true) {
            str_contains($routeName, 'mutations-lulus') => 'mutations-lulus.index',
            str_contains($routeName, 'mutations-do') => 'mutations-do.index',
            default => 'mutations-out.index',
        };

        // Force out_type filter based on route
        if (str_contains($routeName, 'mutations-lulus')) {
            $query->where('out_type', 'graduation');
        } elseif (str_contains($routeName, 'mutations-do')) {
            $query->where('out_type', 'dropout');
        }

        $mutations = $query->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view($view, compact('mutations', 'schools', 'userId'));
    }

    public function create(Request $request)
    {
        $userId = request()->route('userId');
        $schools = School::orderBy('name')->get();

        $schoolContextId = $request->attributes->get('schoolContextId');
        $school = $schoolContextId ? School::find($schoolContextId) : null;
        $schoolGender = $request->attributes->get('schoolGender');

        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::with(['school', 'studyGroup'])->find($request->student_id);
        }

        // Ambil NUPY & nama kepala sekolah
        // Prioritas 1: gtk_employments berdasarkan principal_user_id di school
        // Prioritas 2: school.principal_name / principal_nip / principal_nupy
        $headEmployment = null;
        if ($schoolContextId && $school?->principal_user_id) {
            $headEmployment = \App\Models\GtkEmployment::with('user')
                ->where('school_id', $schoolContextId)
                ->where('user_id', $school->principal_user_id)
                ->first();
        }
        $defaultHeadName = $headEmployment?->user?->name ?? $school?->principal_name ?? '';
        $defaultHeadNupy = $headEmployment?->nupy ?? $school?->principal_nupy ?? $school?->principal_nip ?? '';
        $defaultHeadTitle = 'Kepala Sekolah';

        // Default tanggal Masehi hari ini
        $defaultDate = now()->format('Y-m-d');
        $defaultDateHijri = $this->toHijri($defaultDate);

        // Load all active students grouped by class — no AJAX needed
        $studentQuery = Student::with(['currentClassHistory.studyGroup'])
            ->where('status', 'active');
        if ($schoolContextId) {
            $studentQuery->where('school_id', $schoolContextId);
        }
        if ($schoolGender) {
            $studentQuery->where('gender', $schoolGender === 'putra' ? 'L' : 'P');
        }

        $allStudents = $studentQuery->orderBy('name')->get();
        $groupedStudents = [];
        foreach ($allStudents as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            if (! isset($groupedStudents[$label])) {
                $groupedStudents[$label] = [];
            }
            $groupedStudents[$label][] = $s;
        }

        // Determine out_type from route
        $routeName = $request->route()->getName();
        $defaultOutType = match (true) {
            str_contains($routeName, 'mutations-lulus') => 'graduation',
            str_contains($routeName, 'mutations-do') => 'dropout',
            default => 'mutation',
        };

        return view('mutations-out.create', compact(
            'schools', 'student', 'userId', 'schoolContextId', 'school',
            'groupedStudents', 'defaultOutType',
            'defaultHeadName', 'defaultHeadNupy', 'defaultHeadTitle',
            'defaultDate', 'defaultDateHijri'
        ));
    }

    public function store(Request $request)
    {
        $userId = request()->route('userId');
        $schoolContextId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'nullable|exists:students,id',
            'out_type' => 'required|in:mutation,dropout,graduation',
            'student_name' => 'required|string|max:255',
            'student_nisn' => 'nullable|string|max:20',
            'student_nis' => 'nullable|string|max:20',
            'student_gender' => 'nullable|in:L,P',
            'student_birth_date' => 'nullable|date',
            'student_birth_place' => 'nullable|string|max:100',
            'student_address' => 'nullable|string|max:500',
            'student_previous_school' => 'nullable|string|max:255',
            'student_current_class' => 'nullable|string|max:50',
            'parent_name' => 'nullable|string|max:255',
            'parent_occupation' => 'nullable|string|max:100',
            'parent_address' => 'nullable|string|max:500',
            'parent_phone' => 'nullable|string|max:30',
            // Mutation fields
            'destination_school_name' => 'nullable|string|max:255',
            'destination_school_address' => 'nullable|string|max:500',
            'letter_number' => 'nullable|string|max:50',
            'institution_name' => 'nullable|string|max:255',
            'institution_address' => 'nullable|string|max:500',
            'institution_phone' => 'nullable|string|max:50',
            'institution_email' => 'nullable|email|max:100',
            'head_name' => 'nullable|string|max:100',
            'head_title' => 'nullable|string|max:100',
            'head_nupy' => 'nullable|string|max:50',
            'established_city' => 'nullable|string|max:100',
            'established_date' => 'nullable|date',
            'hijri_date' => 'nullable|string|max:100',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            // Graduation fields
            'graduation_year' => 'nullable|integer|min:1900|max:2100',
            'graduation_certificate_number' => 'nullable|string|max:50',
            'graduation_school_name' => 'nullable|string|max:255',
        ]);

        // Ensure empty student_id becomes null to avoid FK constraint violation
        if (empty($data['student_id'])) {
            unset($data['student_id']);
        }

        try {
            $mutation = StudentMutationOut::create([
                ...$data,
                'requested_by' => Auth::id(),
                'status' => $request->boolean('submit_now') ? 'submitted' : 'draft',
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }

        return redirect()->route('user.mutations-out.show', ['userId' => $userId, 'mutationUuid' => $mutation->id])
            ->with('success', 'PD Keluar berhasil disimpan.');
    }

    public function show(string $userId, string $mutationUuid)
    {
        $mutation = StudentMutationOut::with(['student', 'school', 'requestedBy', 'approvedBy'])
            ->findOrFail($mutationUuid);

        return view('mutations-out.show', compact('mutation', 'userId'));
    }

    public function submit(string $userId, string $mutationUuid)
    {
        $mutation = StudentMutationOut::findOrFail($mutationUuid);
        $mutation->update(['status' => 'submitted']);

        return back()->with('success', 'PD Keluar berhasil diajukan.');
    }

    public function approve(Request $request, string $userId, string $mutationUuid)
    {
        $mutation = StudentMutationOut::with('student')->findOrFail($mutationUuid);
        $mutation->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        if ($mutation->student) {
            $outType = match ($mutation->out_type) {
                'graduation' => \App\Events\StudentMutatedOut::TYPE_GRADUATION,
                'dropout' => \App\Events\StudentMutatedOut::TYPE_DROPOUT,
                default => \App\Events\StudentMutatedOut::TYPE_MUTATION,
            };

            \App\Events\StudentMutatedOut::dispatch(
                student: $mutation->student,
                mutation: $mutation,
                outType: $outType,
                leaveDate: $mutation->established_date?->toDateString() ?? now()->toDateString(),
                actorId: auth()->id(),
            );
        }

        return back()->with('success', 'PD Keluar berhasil disetujui.');
    }

    public function reject(Request $request, string $userId, string $mutationUuid)
    {
        $mutation = StudentMutationOut::findOrFail($mutationUuid);
        $mutation->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'PD Keluar ditolak.');
    }

    public function destroy(string $userId, string $mutationUuid)
    {
        $mutation = StudentMutationOut::findOrFail($mutationUuid);
        if ($mutation->status !== 'draft') {
            return back()->with('error', 'Data yang sudah diajukan tidak bisa dihapus.');
        }
        $mutation->delete();

        return redirect()->route('user.mutations-out.index', ['userId' => $userId])
            ->with('success', 'PD Keluar berhasil dihapus.');
    }

    public function print(string $userId, string $mutationUuid)
    {
        $mutation = StudentMutationOut::with(['student', 'school'])->findOrFail($mutationUuid);
        $school = $mutation->school;
        $html = view('mutations-out.print.pdf', compact('mutation', 'userId', 'school'))->render();
        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->stream('Surat-Pindah-'.($mutation->student_name ?: 'Santri').'.pdf', ['Attachment' => false]);
    }

    private function toHijri(string $date): string
    {
        try {
            $monthsID = [
                'Muharram', 'Safar', 'Rabiul Awwal', 'Rabiul Akhir',
                'Jumadil Awwal', 'Jumadil Akhir', 'Rajab', 'Syakban',
                'Ramadan', 'Syawal', 'Dzulqa\'dah', 'Dzulhijjah',
            ];
            \Pharaonic\Hijri\Hijri::getInstance();
            $h = \Pharaonic\Hijri\Hijri::parse($date);

            return $h->day.' '.$monthsID[$h->month - 1].' '.$h->year.' H';
        } catch (\Throwable $e) {
            return '';
        }
    }

    // API endpoint for client-side Hijri conversion
    public function hijriConvert(Request $request)
    {
        $date = $request->get('date');
        if (! $date) {
            return response()->json(['error' => 'date required'], 422);
        }
        $hijri = $this->toHijri($date);

        return response()->json(compact('hijri'));
    }

    public function findStudent(Request $request)
    {
        $keyword = $request->get('q', '');
        $query = Student::query();
        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('nisn', 'like', "%{$keyword}%")
                  ->orWhere('uuid', 'like', "%{$keyword}%");
            });
        }
        $query->orderBy('name')->take(15);
        $students = $query->get(['id', 'uuid', 'name', 'nisn', 'status']);

        return response()->json($students);
    }
}
