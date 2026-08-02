<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentMahrom;
use Illuminate\Http\Request;

class StudentMahromController extends Controller
{
    /**
     * GET /{userId}/students/mahroms
     * Daftar semua mahrom lintas-santri (halaman "Data Mahrom").
     */
    public function globalIndex(Request $request, string $userId)
    {
        $query = StudentMahrom::query()
            ->with(['student']);

        // Search by mahrom name / phone / id_number
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        // Filter hubungan
        if ($relationship = $request->query('relationship')) {
            $query->where('relationship', $relationship);
        }

        // Filter status aktif
        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        $mahroms = $query
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => StudentMahrom::count(),
            'primary' => StudentMahrom::where('is_primary', true)->count(),
            'active' => StudentMahrom::where('is_active', true)->count(),
        ];

        $relationships = [
            'ayah' => 'Ayah',
            'ibu' => 'Ibu',
            'kakak' => 'Kakak',
            'adik' => 'Adik',
            'paman' => 'Paman',
            'bibi' => 'Bibi',
            'kakek' => 'Kakek',
            'nenek' => 'Nenek',
            'suami' => 'Suami',
            'istri' => 'Istri',
            'sepupu' => 'Sepupu',
            'wali' => 'Wali',
            'anak' => 'Anak',
            'lainnya' => 'Lainnya',
        ];

        return view('students.mahroms.all', compact('mahroms', 'userId', 'stats', 'relationships'));
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom
     */
    public function index(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::findOrFail($santriUuid);

        $mahroms = StudentMahrom::where('student_id', $santriUuid)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $primaryCount = StudentMahrom::where('student_id', $santriUuid)
            ->where('is_primary', true)
            ->count();
        $activeCount = StudentMahrom::where('student_id', $santriUuid)
            ->where('is_active', true)
            ->count();

        return view('students.mahroms.index', compact('student', 'mahroms', 'userId', 'primaryCount', 'activeCount'));
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom/tambah
     */
    public function create(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $existingCount = StudentMahrom::where('student_id', $santriUuid)->count();

        if ($existingCount >= 4) {
            return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
                ->with('error', 'Maksimal 4 mahrom per santri sudah tercapai.');
        }

        return view('students.mahroms.create', compact('student', 'userId'));
    }

    /**
     * POST /{userId}/santri/{santriUuid}/mahrom
     */
    public function store(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $existingCount = StudentMahrom::where('student_id', $santriUuid)->count();

        if ($existingCount >= 4) {
            return back()->withInput()->withErrors(['max' => 'Maksimal 4 mahrom per santri.']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'id_number' => 'nullable|string|max:30|unique:student_mahroms,id_number',
            'relationship' => 'required|in:ayah,ibu,kakak,adik,paman,bibi,kakek,nenek,suami,istri,sepupu,wali,anak,lainnya',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('students/mahroms', 'public');
        }

        $data['student_id'] = $santriUuid;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_primary'] = $request->boolean('is_primary');

        // Pastikan hanya 1 mahrom primer
        if ($data['is_primary']) {
            StudentMahrom::where('student_id', $santriUuid)->update(['is_primary' => false]);
        }

        StudentMahrom::create($data);

        return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
            ->with('success', 'Data mahrom berhasil ditambahkan.');
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}
     */
    public function show(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $student = Student::with([
            'school',
            'currentClassHistory.studyGroup.gradeLevel',
            'activeDormitoryResident.room',
            'province',
            'city',
            'district',
            'village',
        ])->findOrFail($santriUuid);

        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);

        // Daftar mahrom lain milik Santri yang sama (di luar $mahrom)
        $otherMahroms = StudentMahrom::where('student_id', $santriUuid)
            ->where('id', '!=', $mahrom->id)
            ->orderByDesc('is_primary')
            ->orderBy('relationship')
            ->orderBy('name')
            ->get();

        return view('students.mahroms.show', compact('student', 'mahrom', 'otherMahroms', 'userId'));
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}/edit
     */
    public function edit(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $student = Student::findOrFail($santriUuid);
        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);

        return view('students.mahroms.edit', compact('student', 'mahrom', 'userId'));
    }

    /**
     * PUT /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}
     */
    public function update(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'id_number' => 'nullable|string|max:30|unique:student_mahroms,id_number,'.$mahromUuid,
            'relationship' => 'required|in:ayah,ibu,kakak,adik,paman,bibi,kakek,nenek,suami,istri,sepupu,wali,anak,lainnya',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('students/mahroms', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_primary'] = $request->boolean('is_primary');

        if ($data['is_primary']) {
            StudentMahrom::where('student_id', $santriUuid)->where('id', '!=', $mahromUuid)->update(['is_primary' => false]);
        }

        $mahrom->update($data);

        return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
            ->with('success', 'Data mahrom berhasil diperbarui.');
    }

    /**
     * DELETE /{userId}/santri/{santriUuid}/mahrom/{mahromUuid}
     */
    public function destroy(Request $request, string $userId, string $santriUuid, string $mahromUuid)
    {
        $mahrom = StudentMahrom::where('student_id', $santriUuid)->findOrFail($mahromUuid);
        $mahrom->delete();

        return redirect()->route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $santriUuid])
            ->with('success', 'Mahrom berhasil dihapus.');
    }

    /**
     * GET /{userId}/students/mahroms/tambah
     * Form tambah mahrom lintas-santri (operator harus pilih siswa).
     */
    public function globalCreate(Request $request, string $userId)
    {
        $students = $this->listStudentsForSelect();
        $preselectedStudentId = $request->query('student_id');

        return view('students.mahroms.create-global', [
            'userId' => $userId,
            'students' => $students,
            'preselectedStudentId' => $preselectedStudentId,
            'breadcrumbs' => true,
        ]);
    }

    /**
     * POST /{userId}/students/mahroms
     * Simpan mahrom baru (Santri dipilih via form).
     */
    public function globalStore(Request $request, string $userId)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'name' => 'required|string|max:191',
            'id_number' => 'nullable|string|max:30|unique:student_mahroms,id_number',
            'relationship' => 'required|in:ayah,ibu,kakak,adik,paman,bibi,kakek,nenek,suami,istri,sepupu,wali,anak,lainnya',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $existingCount = StudentMahrom::where('student_id', $data['student_id'])->count();
        if ($existingCount >= 4) {
            return back()->withInput()->withErrors(['student_id' => 'Santri terkait sudah memiliki 4 mahrom (maks). Hapus salah satu atau pilih Santri lain.']);
        }

        $student = Student::findOrFail($data['student_id']);
        // Validasi scoped-school (students.user_id selalu NULL, gunakan school_id)
        if (! $this->canAccessStudent($student)) {
            return back()->withInput()->withErrors(['student_id' => 'Santri tidak ditemukan pada akun ini.']);
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('students/mahroms', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_primary'] = $request->boolean('is_primary');

        if ($data['is_primary']) {
            StudentMahrom::where('student_id', $data['student_id'])->update(['is_primary' => false]);
        }

        StudentMahrom::create($data);

        return redirect()->route('user.students.mahroms.global', ['userId' => $userId])
            ->with('success', 'Data mahrom berhasil ditambahkan.');
    }

    /**
     * Helper: apakah user berwenang mengelola Santri ini?
     * Mengikuti SchoolContextMiddleware — global view bypass, selain itu
     * sekolah Santri harus sama dengan school context operator.
     */
    protected function canAccessStudent($student): bool
    {
        if (! $student) {
            return false;
        }
        if (request()->boolean('isGlobalView')) {
            return true;
        }
        $ctx = request()->schoolContextId;
        if (! $ctx) {
            return true; // tanpa school context (mis. super admin), ijinkan
        }

        return $student->school_id === $ctx;
    }

    /**
     * GET /{userId}/students/mahroms/{mahromUuid}/edit
     * Detail mahrom lintas-santri (tanpa scope ke satu Santri).
     * Mirip dengan `show`, tapi tidak membatasi student_id.
     */
    public function globalShow(Request $request, string $userId, string $mahromUuid)
    {
        $mahrom = StudentMahrom::with(['student', 'student.activeDormitoryResident.room'])->findOrFail($mahromUuid);

        return view('students.mahroms.show-global', compact('mahrom', 'userId'));
    }

    /**
     * Form edit mahrom lintas-santri.
     */
    public function globalEdit(Request $request, string $userId, string $mahromUuid)
    {
        $mahrom = StudentMahrom::with('student')->findOrFail($mahromUuid);

        if (! $mahrom->student || (! $this->canAccessStudent($mahrom->student))) {
            abort(404);
        }

        $students = $this->listStudentsForSelect();

        return view('students.mahroms.edit-global', [
            'userId' => $userId,
            'mahrom' => $mahrom,
            'student' => $mahrom->student,
            'students' => $students,
        ]);
    }

    /**
     * PUT /{userId}/students/mahroms/{mahromUuid}
     * Simpan perubahan mahrom. Boleh pindah ke Santri lain.
     */
    public function globalUpdate(Request $request, string $userId, string $mahromUuid)
    {
        $mahrom = StudentMahrom::with('student')->findOrFail($mahromUuid);

        if (! $mahrom->student || (! $this->canAccessStudent($mahrom->student))) {
            abort(404);
        }

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'name' => 'required|string|max:191',
            'id_number' => 'nullable|string|max:30|unique:student_mahroms,id_number,'.$mahromUuid,
            'relationship' => 'required|in:ayah,ibu,kakak,adik,paman,bibi,kakek,nenek,suami,istri,sepupu,wali,anak,lainnya',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Jika pindah ke Santri lain, cek kapasitas max 4.
        if ($data['student_id'] !== $mahrom->student_id) {
            $existingCount = StudentMahrom::where('student_id', $data['student_id'])->count();
            if ($existingCount >= 4) {
                return back()->withInput()->withErrors(['student_id' => 'Santri tujuan sudah memiliki 4 mahrom (maks).']);
            }
            $newStudent = Student::findOrFail($data['student_id']);
            // Validasi scoped-school (students.user_id selalu NULL, gunakan school_id)
            if (! $this->canAccessStudent($newStudent)) {
                return back()->withInput()->withErrors(['student_id' => 'Santri tidak ditemukan pada akun ini.']);
            }
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('students/mahroms', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_primary'] = $request->boolean('is_primary');

        // Reset primary di student asal dan student tujuan.
        if ($data['is_primary']) {
            StudentMahrom::where('student_id', $data['student_id'])->where('id', '!=', $mahromUuid)->update(['is_primary' => false]);
        }

        $mahrom->update($data);

        return redirect()->route('user.students.mahroms.global', ['userId' => $userId])
            ->with('success', 'Data mahrom berhasil diperbarui.');
    }

    /**
     * DELETE /{userId}/students/mahroms/{mahromUuid}
     */
    public function globalDestroy(Request $request, string $userId, string $mahromUuid)
    {
        $mahrom = StudentMahrom::with('student')->findOrFail($mahromUuid);
        if (! $mahrom->student || (! $this->canAccessStudent($mahrom->student))) {
            abort(404);
        }

        $mahrom->delete();

        return redirect()->route('user.students.mahroms.global', ['userId' => $userId])
            ->with('success', 'Mahrom berhasil dihapus.');
    }

    /**
     * Daftar siswa untuk dropdown Pilih Santri — filter sesuai akun login.
     * Eager-load kelas aktif (StudentClassHistory) dan kamar (DormitoryResident)
     * untuk ditampilkan di label option.
     */
    protected function listStudentsForSelect(): array
    {
        $auth = auth()->user();
        $query = Student::query()
            ->with([
                'currentClassHistory.studyGroup.gradeLevel',
                'activeDormitoryResident.room',
            ])
            ->orderBy('name');

        // Filter by user_id if column populated, else fall back to school_id.
        if (Student::query()->whereNotNull('user_id')->exists()) {
            if ($auth) {
                $query->where('user_id', $auth->id);
            }
        } elseif ($auth && isset($auth->school_id)) {
            $query->where('school_id', $auth->school_id);
        }

        return $query->limit(2000)->get(['id', 'name', 'nisn'])->all();
    }

    /**
     * GET /{userId}/santri/{santriUuid}/mahrom/list
     * Endpoint JSON kecil untuk preview daftar mahrom seorang Santri
     * (dipakai di form global create/edit agar operator tahu sebelum pilih).
     */
    public function listForStudent(string $userId, string $santriUuid)
    {
        $student = Student::find($santriUuid);
        if (! $student) {
            return response()->json(['ok' => false, 'message' => 'Santri tidak ditemukan.'], 404);
        }

        if (! $this->canAccessStudent($student)) {
            return response()->json(['ok' => false, 'message' => 'Santri bukan milik sekolah Anda.'], 403);
        }

        $mahroms = StudentMahrom::where('student_id', $santriUuid)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'relationship', 'phone', 'is_primary', 'is_active'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'relationship' => $m->relationship,
                'relationship_text' => $m->relationship_text,
                'phone' => $m->phone,
                'is_primary' => (bool) $m->is_primary,
                'is_active' => (bool) $m->is_active,
            ]);

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->nisn,
            ],
            'count' => $mahroms->count(),
            'max' => 4,
            'mahroms' => $mahroms,
        ]);
    }
}
