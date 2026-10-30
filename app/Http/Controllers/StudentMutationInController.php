<?php

namespace App\Http\Controllers;

use App\Events\StudentMutatedIn;
use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMutationIn;
use App\Models\StudentMutationSetting;
use App\Models\StudyGroup;
use App\Services\Documents\DocumentSigningService;
use App\Services\Documents\DocumentVisualService;
use App\Services\Documents\SchoolLeaderService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Statistik status — dihitung dari query yang sudah school-scoped & search-aware.
        // Filter status baru diterapkan pada listing setelah clone agar kartu tetap
        // menampilkan sebaran status (COUNT murah, tanpa memuat data).
        $statQuery = clone $query;
        $statistics = [
            'total' => (clone $statQuery)->count(),
            'draft' => (clone $statQuery)->where('status', 'draft')->count(),
            'submitted' => (clone $statQuery)->where('status', 'submitted')->count(),
            'approved' => (clone $statQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $statQuery)->where('status', 'rejected')->count(),
        ];

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $mutations = $query->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view('mutations-in.index', compact('mutations', 'schools', 'userId', 'statistics'));
    }

    public function create(Request $request)
    {
        $userId = request()->route('userId');
        $schools = School::orderBy('name')->get();

        $schoolContextId = $request->attributes->get('schoolContextId');
        $school = $schoolContextId ? School::find($schoolContextId) : null;
        $schoolGender = $request->attributes->get('schoolGender');

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
        $leader = app(SchoolLeaderService::class)->forSchool($school);
        $defaultHeadName = $leader['display_name'];
        $defaultHeadNupy = $leader['nupy'];
        $defaultHeadTitle = 'Kepala Sekolah';

        $defaultDate = now()->format('Y-m-d');
        $defaultDateHijri = $this->toHijri($defaultDate);

        // Auto-generate NIS untuk sekolah ini
        $maxNis = Student::where('school_id', $schoolContextId)->max('nis');
        $nextNis = $maxNis ? (intval($maxNis) + 1) : 1;
        $defaultNis = $student?->nis ?? str_pad($nextNis, 4, '0', STR_PAD_LEFT);

        // Daftar santri terdaftar — dipakai untuk mengisi otomatis formulir bila
        // mutasi masuk ditujukan ke santri yang sudah ada di satuan pendidikan ini.
        $studentQuery = Student::with(['currentClassHistory.studyGroup', 'mahroms', 'primaryMahrom'])
            ->where('status', 'active')
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId));

        $allStudents = $studentQuery->orderBy('name')->get();

        if ($schoolGender) {
            $preferredGender = $schoolGender === 'putra' ? 'L' : 'P';
            $genderFiltered = $allStudents->where('gender', $preferredGender);

            if ($genderFiltered->isNotEmpty()) {
                $allStudents = $genderFiltered->values();
            }
        }

        $groupedStudents = [];
        $studentParentData = [];
        foreach ($allStudents as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            $groupedStudents[$label][] = $s;
            $studentParentData[$s->id] = $this->parentDataFor($s);
        }

        $mutationLayout = StudentMutationSetting::forSchool($schoolContextId)->layoutSettings();

        return view('mutations-in.create', compact(
            'schools', 'userId', 'schoolContextId', 'school', 'student', 'schoolGender',
            'groupedStudents', 'studentParentData', 'mutationLayout',
            'defaultHeadName', 'defaultHeadNupy', 'defaultHeadTitle',
            'defaultDate', 'defaultDateHijri', 'defaultNis'
        ));
    }

    /**
     * Data orang tua/wali untuk pengisian otomatis formulir.
     *
     * @return array{name: string, occupation: string, phone: string, address: string}
     */
    private function parentDataFor(Student $student): array
    {
        $mahroms = $student->mahroms ?? collect();
        $parent = $student->primaryMahrom
            ?: $mahroms->firstWhere('is_primary', true)
            ?: $mahroms->firstWhere('relationship', 'ayah')
            ?: $mahroms->firstWhere('relationship', 'ibu')
            ?: $mahroms->firstWhere('relationship', 'wali')
            ?: $mahroms->first();

        return [
            'name' => (string) ($parent?->name ?: ($student->father_name ?: ($student->mother_name ?: ($student->guardian_name ?: '')))),
            'occupation' => (string) ($student->father_occupation ?: ($student->mother_occupation ?: ($student->guardian_occupation ?: ''))),
            'phone' => (string) ($parent?->phone ?: ($student->phone ?: ($student->mobile_phone ?: ''))),
            'address' => (string) ($parent?->address ?: ($student->address ?: '')),
        ];
    }

    public function store(Request $request)
    {
        $userId = request()->route('userId');
        $schoolContextId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'school_id' => ($schoolContextId ? 'nullable' : 'required').'|exists:schools,id',
            'student_id' => 'nullable|exists:students,id',
            'student_name' => 'required|string|max:255',
            'student_nisn' => 'nullable|string|max:20',
            'student_nis' => 'nullable|string|max:20',
            'student_birth_place' => 'required|string|max:100',
            'student_birth_date' => 'required|date',
            'student_gender' => 'required|in:L,P',
            'student_religion' => 'nullable|string|max:50',
            'student_address' => 'nullable|string|max:500',
            'student_previous_school' => 'required|string|max:255',
            'student_previous_class' => 'nullable|string|max:50',
            'student_current_class' => 'nullable|string|max:50',
            'father_name' => 'required|string|max:255',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'required|string|max:255',
            'mother_occupation' => 'nullable|string|max:100',
            'parent_name' => 'nullable|string|max:255',
            'parent_occupation' => 'nullable|string|max:100',
            'parent_address' => 'nullable|string|max:500',
            'parent_phone' => 'required|string|max:30',
            'accepted_class' => 'required|string|max:50',
            'accepted_semester' => 'nullable|string|max:50',
            'accepted_academic_year' => 'required|string|max:100',
            'letter_number' => 'nullable|string|max:50',
            'established_city' => 'nullable|string|max:100',
            'established_date' => 'nullable|date',
            'hijri_date' => 'nullable|string|max:100',
            'head_name' => 'nullable|string|max:100',
            'head_title' => 'nullable|string|max:100',
            'head_nupy' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ], [
            // Pesan umum dalam bahasa Indonesia — tidak menampilkan nama kolom
            // database agar struktur tabel tidak terbaca dari pesan error.
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'in' => ':attribute yang dipilih tidak valid.',
            'exists' => ':attribute yang dipilih tidak ditemukan.',
            'email' => ':attribute harus berupa alamat email yang valid.',
        ], [
            'school_id' => 'satuan pendidikan',
            'student_id' => 'santri',
            'student_name' => 'nama lengkap santri',
            'student_nisn' => 'NISN',
            'student_nis' => 'NIS',
            'student_birth_place' => 'tempat lahir',
            'student_birth_date' => 'tanggal lahir',
            'student_gender' => 'jenis kelamin',
            'student_religion' => 'agama',
            'student_address' => 'alamat santri',
            'student_previous_school' => 'sekolah asal',
            'student_previous_class' => 'kelas di sekolah asal',
            'student_current_class' => 'kelas saat ini',
            'father_name' => 'nama bapak',
            'father_occupation' => 'pekerjaan bapak',
            'mother_name' => 'nama ibu',
            'mother_occupation' => 'pekerjaan ibu',
            'parent_name' => 'nama orang tua/wali',
            'parent_occupation' => 'pekerjaan orang tua/wali',
            'parent_address' => 'alamat orang tua/wali',
            'parent_phone' => 'nomor HP orang tua/wali',
            'accepted_class' => 'kelas diterima',
            'accepted_semester' => 'semester',
            'accepted_academic_year' => 'tahun ajaran',
            'letter_number' => 'nomor surat',
            'established_city' => 'kota penetapan',
            'established_date' => 'tanggal penetapan',
            'hijri_date' => 'tanggal hijriyah',
            'head_name' => 'nama kepala sekolah',
            'head_title' => 'jabatan kepala sekolah',
            'head_nupy' => 'NUPY kepala sekolah',
            'notes' => 'catatan',
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

        // Tanggal hijriyah dihitung ulang di server bila tidak terkirim dari form
        // (mis. JavaScript dinonaktifkan), agar surat tetap lengkap.
        if (empty($data['hijri_date']) && ! empty($data['established_date'])) {
            $data['hijri_date'] = $this->toHijri($data['established_date']);
        }

        try {
            $mutation = StudentMutationIn::create([
                ...$data,
                'requested_by' => Auth::id(),
                'status' => $request->boolean('submit_now') ? 'submitted' : 'draft',
            ]);
        } catch (\Throwable $e) {
            // Detail teknis hanya masuk log — pesan ke pengguna tidak boleh
            // membocorkan nama kolom/tabel maupun query database.
            Log::error('Gagal menyimpan data PD Masuk.', [
                'user_id' => Auth::id(),
                'school_id' => $data['school_id'] ?? $schoolContextId,
                'exception' => $e,
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'Data PD Masuk gagal disimpan. Periksa kembali isian Anda lalu coba lagi.');
        }

        return redirect()->route('user.mutations-in.show', ['userId' => $userId, 'mutationUuid' => $mutation->id])
            ->with('success', 'PD Masuk berhasil disimpan.');
    }

    /**
     * Pratinjau PDF dari isian formulir (belum disimpan).
     */
    public function previewPdf(Request $request)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $schoolId = $schoolContextId ?: $request->input('school_id');
        $school = $schoolId ? School::find($schoolId) : null;
        abort_unless($school, 422, 'Satuan pendidikan tidak ditemukan untuk pratinjau.');
        $principal = app(SchoolLeaderService::class)->forSchool($school);

        // Hanya nilai skalar yang diteruskan ke model — mencegah payload array
        // masuk ke atribut teks saat pratinjau.
        // Kolom tanggal dikecualikan dari mass assignment agar nilai tidak valid
        // tidak melempar exception saat cast, lalu diisi lewat safeDate().
        $input = array_filter(
            $request->except([
                '_token', '_method', 'submit_now', 'student_id', 'school_id',
                'student_birth_date', 'established_date',
            ]),
            fn ($value) => is_scalar($value) || is_null($value)
        );

        $mutation = new StudentMutationIn($input);

        $mutation->school_id = $school->id;
        $mutation->status = 'draft';
        $mutation->requested_by = Auth::id();
        $mutation->student_birth_date = $this->safeDate($request->input('student_birth_date'));
        $mutation->established_date = $this->safeDate($request->input('established_date'), now());
        $mutation->setRelation('school', $school);

        $layout = StudentMutationSetting::forSchool($school->id)->layoutSettings();
        $documentMedia = app(DocumentVisualService::class)->viewData($school, 'mutasi');

        $html = view('mutations-in.print.pdf', [
            'mutation' => $mutation,
            'userId' => $request->route('userId'),
            'school' => $school,
            'layout' => $layout,
            'principal' => $principal,
            // QR final baru diterbitkan saat dokumen disimpan.
            'verification' => ['preview' => true],
            'documentMedia' => $documentMedia,
        ])->render();

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper($this->dompdfPaper($layout['paper_size'] ?? 'a4'));
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Preview-Surat-Rekomendasi.pdf"',
        ]);
    }

    /**
     * Simpan pengaturan layout surat untuk satuan pendidikan aktif.
     */
    public function saveLayout(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId') ?: $request->input('school_id');
        abort_unless($schoolId && School::whereKey($schoolId)->exists(), 422, 'Satuan pendidikan tidak ditemukan.');

        $validated = $request->validate([
            'margin_top' => 'required|numeric|min:0|max:50',
            'margin_right' => 'required|numeric|min:0|max:50',
            'margin_bottom' => 'required|numeric|min:0|max:50',
            'margin_left' => 'required|numeric|min:0|max:50',
            'font_family' => 'required|in:Times New Roman,Arial,Georgia,Bookman Old Style,Tahoma',
            'font_size' => 'required|numeric|min:6|max:24',
            'line_height' => 'required|numeric|min:1|max:2.5',
            'paper_size' => 'required|in:a4,folio,letter',
            'show_kop' => 'nullable|boolean',
        ], [
            'required' => ':attribute wajib diisi.',
            'numeric' => ':attribute harus berupa angka.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'in' => ':attribute yang dipilih tidak valid.',
        ], [
            'margin_top' => 'margin atas',
            'margin_right' => 'margin kanan',
            'margin_bottom' => 'margin bawah',
            'margin_left' => 'margin kiri',
            'font_family' => 'jenis font',
            'font_size' => 'ukuran font',
            'line_height' => 'jarak baris',
            'paper_size' => 'ukuran kertas',
        ]);

        StudentMutationSetting::updateOrCreate(
            ['school_id' => $schoolId],
            $validated + [
                'show_kop' => $request->boolean('show_kop'),
                'updated_by' => $request->user()?->id,
            ]
        );

        return back()->with('success', 'Pengaturan layout surat berhasil disimpan.');
    }

    private function safeDate(mixed $value, ?Carbon $fallback = null): ?Carbon
    {
        if (blank($value) || ! is_string($value)) {
            return $fallback;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function dompdfPaper(string $paper): string
    {
        return match ($paper) {
            'folio' => 'folio',
            'letter' => 'letter',
            default => 'A4',
        };
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

        $mutation->update(['status' => 'submitted', 'submitted_at' => now()]);

        return back()->with('success', 'PD Masuk berhasil diajukan.');
    }

    public function approve(Request $request, string $userId, string $mutationUuid)
    {
        $mutation = $this->findScopedMutation($mutationUuid)->load('student');

        if (in_array($mutation->status, ['approved', 'rejected'], true)) {
            return back()->with('error', 'Data mutasi ini sudah diproses sebelumnya.');
        }

        $this->approveMutation($mutation);

        return back()->with('success', 'PD Masuk berhasil disetujui. Santri sudah masuk ke Data Santri.');
    }

    /**
     * Aksi massal: submit / approve / reject untuk data terpilih.
     */
    public function bulk(Request $request, string $userId)
    {
        $data = $request->validate([
            'action' => 'required|in:submit,approve,reject',
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $schoolContextId = $request->attributes->get('schoolContextId');

        $mutations = StudentMutationIn::query()
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->whereIn('id', $data['ids'])
            ->get();

        $processed = 0;
        $skipped = 0;

        foreach ($mutations as $mutation) {
            $final = in_array($mutation->status, ['approved', 'rejected'], true);

            if ($data['action'] === 'submit' && $mutation->status === 'draft') {
                $mutation->update(['status' => 'submitted', 'submitted_at' => now()]);
                $processed++;
            } elseif ($data['action'] === 'approve' && ! $final) {
                $this->approveMutation($mutation);
                $processed++;
            } elseif ($data['action'] === 'reject' && ! $final) {
                $mutation->update([
                    'status' => 'rejected',
                    'rejection_reason' => $data['rejection_reason'] ?? 'Ditolak massal',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                ]);
                $processed++;
            } else {
                $skipped++;
            }
        }

        $message = "Aksi massal: {$processed} data diproses";
        if ($skipped > 0) {
            $message .= ", {$skipped} dilewati (status tidak sesuai)";
        }

        return back()->with($processed > 0 ? 'success' : 'error', $message.'.');
    }

    /**
     * Inti approval (dipakai tombol tunggal & aksi massal).
     */
    private function approveMutation(StudentMutationIn $mutation): void
    {
        $mutation->loadMissing('student');

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
    }

    public function reject(Request $request, string $userId, string $mutationUuid)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ], [
            'max' => 'alasan penolakan maksimal :max karakter.',
            'string' => 'alasan penolakan harus berupa teks.',
        ]);

        $mutation = $this->findScopedMutation($mutationUuid);

        if (in_array($mutation->status, ['approved', 'rejected'], true)) {
            return back()->with('error', 'Data mutasi ini sudah diproses sebelumnya.');
        }

        $mutation->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
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
        $principal = app(SchoolLeaderService::class)->forSchool($school);
        $layout = StudentMutationSetting::forSchool($school?->id)->layoutSettings();

        try {
            $verification = $this->issueVerification($mutation);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        $documentMedia = app(DocumentVisualService::class)->viewData($school, 'mutasi');
        $html = view('mutations-in.print.pdf', compact('mutation', 'userId', 'school', 'layout', 'verification', 'documentMedia', 'principal'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper($this->dompdfPaper($layout['paper_size'] ?? 'a4'));
        $dompdf->render();
        $pdf = (string) $dompdf->output();

        if ($verification) {
            app(DocumentSigningService::class)->finalize($verification['document'], $pdf);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Surat-Rekomendasi-'.($mutation->student_name ?: 'Santri').'.pdf"',
        ]);
    }

    /**
     * Daftarkan surat ke registry verifikasi publik. Kegagalan registry tidak
     * boleh menghalangi pencetakan dokumen resmi.
     */
    private function issueVerification(StudentMutationIn $mutation): ?array
    {
        // Mode PAdES ditolak selama PSrE belum siap: jangan terbit dokumen
        // yang tampak sah tanpa signature.
        $mode = app(DocumentVisualService::class)->modeForPrinting($mutation->school, 'mutasi');

        if ($mode === 'visual') {
            return null;
        }

        try {
            $leader = app(SchoolLeaderService::class)->forSchool($mutation->school);
            $signerName = $mutation->head_name ?: $leader['display_name'];
            $signers = [];
            if ($signerName) {
                $signers[] = [
                    'name' => $signerName,
                    'role' => $mutation->head_title ?: 'Kepala Sekolah',
                ];
            }

            return app(DocumentSigningService::class)->issue([
                'document_type' => 'mutasi',
                'document_number' => $mutation->letter_number,
                'title' => 'Surat Rekomendasi Masuk',
                'school_id' => $mutation->school_id,
                'subject_type' => 'student',
                'subject_id' => $mutation->student_id,
                'issued_by' => Auth::id(),
                'metadata' => [
                    'direction' => 'in',
                    'subtype' => 'mutasi_masuk',
                ],
                'validation_mode' => $mode,
            ], $signers);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Gagal menerbitkan registry surat mutasi masuk: '.$e->getMessage());

            return null;
        }
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
