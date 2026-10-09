<?php

namespace App\Http\Controllers;

use App\Events\StudentMutatedIn;
use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMutationIn;
use App\Models\StudyGroup;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Pharaonic\Hijri\Hijri;

class StudentMutationInController extends Controller
{
    public function index(Request $request)
    {
        $userId = request()->route('userId');

        $query = StudentMutationIn::with(['student', 'school', 'requestedBy'])
            ->orderByDesc('created_at');

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

        $mutations = $query->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view('mutations-in.index', compact('mutations', 'schools', 'userId'));
    }

    public function create(Request $request)
    {
        $userId = request()->route('userId');
        $schools = School::orderBy('name')->get();

        $schoolContextId = $request->attributes->get('schoolContextId');
        $school = $schoolContextId ? School::find($schoolContextId) : null;

        // Prefill dari halaman Data Santri: mutasi masuk untuk santri yang sudah terdaftar
        // (mis. pindah sekolah internal) tidak boleh membuat baris Student baru.
        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::query()
                ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
                ->find($request->student_id);
        }
        if ($request->filled('student_id') && ! $student) {
            return redirect()->route('user.mutations-in.index', ['userId' => $userId])
                ->with('error', 'Santri tidak ditemukan pada satuan pendidikan Anda.');
        }

        $headEmployment = null;
        if ($schoolContextId && $school?->principal_user_id) {
            $headEmployment = GtkEmployment::with('user')
                ->where('school_id', $schoolContextId)
                ->where('user_id', $school->principal_user_id)
                ->first();
        }
        $defaultHeadName = $headEmployment?->user?->name ?? $school?->principal_name ?? '';
        $defaultHeadNupy = $headEmployment?->nupy ?? $school?->principal_nupy ?? $school?->principal_nip ?? '';
        $defaultHeadTitle = 'Kepala Sekolah';

        $defaultDate = now()->format('Y-m-d');
        $defaultDateHijri = $this->toHijri($defaultDate);

        // Auto-generate NIS untuk sekolah ini
        $maxNis = Student::where('school_id', $schoolContextId)->max('nis');
        $nextNis = $maxNis ? (intval($maxNis) + 1) : 1;
        $defaultNis = $student?->nis ?? str_pad($nextNis, 4, '0', STR_PAD_LEFT);

        return view('mutations-in.create', compact(
            'schools', 'userId', 'schoolContextId', 'school', 'student',
            'defaultHeadName', 'defaultHeadNupy', 'defaultHeadTitle',
            'defaultDate', 'defaultDateHijri', 'defaultNis'
        ));
    }

    public function store(Request $request)
    {
        $userId = request()->route('userId');
        $schoolContextId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'nullable|exists:students,id',
            'student_name' => 'required|string|max:255',
            'student_nisn' => 'nullable|string|max:20',
            'student_nis' => 'nullable|string|max:20',
            'student_birth_place' => 'nullable|string|max:100',
            'student_birth_date' => 'nullable|date',
            'student_gender' => 'nullable|in:L,P',
            'student_religion' => 'nullable|string|max:50',
            'student_previous_school' => 'nullable|string|max:255',
            'student_previous_class' => 'nullable|string|max:50',
            'father_name' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:100',
            'parent_address' => 'nullable|string|max:500',
            'parent_phone' => 'nullable|string|max:30',
            'accepted_class' => 'nullable|string|max:50',
            'accepted_semester' => 'nullable|string|max:50',
            'accepted_academic_year' => 'nullable|string|max:100',
            'letter_number' => 'nullable|string|max:50',
            'established_city' => 'nullable|string|max:100',
            'established_date' => 'nullable|date',
            'hijri_date' => 'nullable|string|max:100',
            'head_name' => 'nullable|string|max:100',
            'head_title' => 'nullable|string|max:100',
            'head_nupy' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if (empty($data['student_id'])) {
            unset($data['student_id']);
        } elseif (! Student::where('id', $data['student_id'])
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->exists()) {
            return redirect()->back()->withInput()->with('error', 'Santri terpilih tidak ditemukan pada satuan pendidikan Anda.');
        }

        // Satuan pendidikan aktif selalu menjadi pemilik data mutasi.
        if ($schoolContextId) {
            $data['school_id'] = $schoolContextId;
        }

        try {
            $mutation = StudentMutationIn::create([
                ...$data,
                'requested_by' => Auth::id(),
                'status' => $request->boolean('submit_now') ? 'submitted' : 'draft',
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }

        return redirect()->route('user.mutations-in.show', ['userId' => $userId, 'mutationUuid' => $mutation->id])
            ->with('success', 'PD Masuk berhasil disimpan.');
    }

    public function show(string $userId, string $mutationUuid)
    {
        $mutation = $this->findScopedMutation($mutationUuid)
            ->load(['student', 'school', 'requestedBy', 'approvedBy']);

        return view('mutations-in.show', compact('mutation', 'userId'));
    }

    public function submit(string $userId, string $mutationUuid)
    {
        $mutation = $this->findScopedMutation($mutationUuid);

        if ($mutation->status !== 'draft') {
            return back()->with('error', 'Hanya data berstatus draft yang dapat diajukan.');
        }

        $mutation->update(['status' => 'submitted']);

        return back()->with('success', 'PD Masuk berhasil diajukan.');
    }

    public function approve(Request $request, string $userId, string $mutationUuid)
    {
        $mutation = $this->findScopedMutation($mutationUuid)->load('student');

        if (in_array($mutation->status, ['approved', 'rejected'], true)) {
            return back()->with('error', 'Data mutasi ini sudah diproses sebelumnya.');
        }

        $mutation->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Simpan / update data ke tabel students
        $studentData = [
            'school_id' => $mutation->school_id,
            'nisn' => $mutation->student_nisn,
            'nis' => $mutation->student_nis,
            'name' => $mutation->student_name,
            'gender' => $mutation->student_gender,
            'birth_place' => $mutation->student_birth_place,
            'birth_date' => $mutation->student_birth_date,
            'religion' => $mutation->student_religion ?? 'Islam',
            'address' => $mutation->parent_address,
            'phone' => $mutation->parent_phone,
            'father_name' => $mutation->father_name,
            'father_occupation' => $mutation->father_occupation,
            'mother_name' => $mutation->mother_name,
            'mother_occupation' => $mutation->mother_occupation,
            'previous_school' => $mutation->student_previous_school,
            'entry_date' => $mutation->established_date,
            'status' => 'active',
        ];

        $student = DB::transaction(function () use ($mutation, $studentData) {
            if ($mutation->student_id && $mutation->student) {
                // Perbarui hanya nilai yang terisi — kolom wajib (gender dll.)
                // tidak boleh tertimpa null dari form mutasi.
                $mutation->student->update(array_filter($studentData, fn ($v) => $v !== null));

                return $mutation->student;
            }

            $student = Student::create($studentData);
            $mutation->update(['student_id' => $student->id]);

            return $student;
        });

        $targetStudyGroup = null;
        $targetAcademicYear = null;
        if (! empty($mutation->accepted_class) && ! empty($mutation->accepted_academic_year)) {
            $targetAcademicYear = AcademicYear::where('name', $mutation->accepted_academic_year)
                ->orWhere('id', $mutation->accepted_academic_year)
                ->first();
            if ($targetAcademicYear) {
                $targetStudyGroup = StudyGroup::where('school_id', $mutation->school_id)
                    ->where('academic_year_id', $targetAcademicYear->id)
                    ->where('name', $mutation->accepted_class)
                    ->first();
            }
        }

        StudentMutatedIn::dispatch(
            $student,
            $mutation,
            $targetStudyGroup,
            $targetAcademicYear,
            $mutation->established_date?->toDateString() ?? now()->toDateString(),
            auth()->id(),
        );

        return back()->with('success', 'PD Masuk berhasil disetujui. Santri sudah masuk ke Data Santri.');
    }

    public function reject(Request $request, string $userId, string $mutationUuid)
    {
        $mutation = $this->findScopedMutation($mutationUuid);

        if (in_array($mutation->status, ['approved', 'rejected'], true)) {
            return back()->with('error', 'Data mutasi ini sudah diproses sebelumnya.');
        }

        $mutation->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'PD Masuk ditolak.');
    }

    public function destroy(string $userId, string $mutationUuid)
    {
        $mutation = $this->findScopedMutation($mutationUuid);
        if ($mutation->status !== 'draft') {
            return back()->with('error', 'Data yang sudah diajukan tidak bisa dihapus.');
        }
        $mutation->delete();

        return redirect()->route('user.mutations-in.index', ['userId' => $userId])
            ->with('success', 'PD Masuk berhasil dihapus.');
    }

    public function print(string $userId, string $mutationUuid)
    {
        $mutation = $this->findScopedMutation($mutationUuid)->load(['student', 'school']);
        $school = $mutation->school;
        $html = view('mutations-in.print.pdf', compact('mutation', 'userId', 'school'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->stream('Surat-Rekomendasi-'.($mutation->student_name ?: 'Santri').'.pdf', ['Attachment' => false]);
    }

    /**
     * Batasi akses data mutasi ke satuan pendidikan pengguna.
     */
    private function findScopedMutation(string $mutationUuid): StudentMutationIn
    {
        $schoolContextId = request()->attributes->get('schoolContextId');

        return StudentMutationIn::query()
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->findOrFail($mutationUuid);
    }

    private function toHijri(string $date): string
    {
        try {
            $monthsID = [
                'Muharram', 'Safar', 'Rabiul Awwal', 'Rabiul Akhir',
                'Jumadil Awwal', 'Jumadil Akhir', 'Rajab', 'Syakban',
                'Ramadan', 'Syawal', 'Dzulqa\'dah', 'Dzulhijjah',
            ];
            Hijri::getInstance();
            $h = Hijri::parse($date);

            return $h->day.' '.$monthsID[$h->month - 1].' '.$h->year.' H';
        } catch (\Throwable $e) {
            return '';
        }
    }

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
        $schoolContextId = $request->attributes->get('schoolContextId');
        $query = Student::query()->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId));
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
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
