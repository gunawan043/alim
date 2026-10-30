<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GraduationCertificateSetting;
use App\Models\GraduationCertificateSubject;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use App\Models\StudentMutationOut;
use App\Services\Documents\DocumentSigningService;
use App\Services\Documents\DocumentVisualService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Surat Keterangan Lulus (SKL).
 *
 * Kelulusan adalah jalur keluar santri dengan dokumen sendiri, terpisah dari
 * surat mutasi keluar. Format dokumen, kelompok mapel, dan nama mapel yang
 * dicetak diatur per sekolah pada halaman pengaturan.
 */
class GraduationCertificateController extends Controller
{
    public function settings(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);
        abort_unless($schoolId, 404, 'Satuan pendidikan tidak ditemukan.');

        $school = School::with('city')->findOrFail($schoolId);
        $setting = GraduationCertificateSetting::forSchool($schoolId);

        $rows = GraduationCertificateSubject::where('school_id', $schoolId)
            ->ordered()
            ->get();

        $masterSubjects = Subject::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Contoh santri lulus untuk pratinjau.
        $sampleStudents = Student::where('school_id', $schoolId)
            ->where('status', 'graduate')
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'graduation_year']);

        $groups = GraduationCertificateSetting::GROUPS;

        return view('students.graduation-certificate.settings', compact(
            'userId', 'school', 'schoolId', 'setting', 'rows', 'masterSubjects', 'sampleStudents', 'groups'
        ));
    }

    public function saveSettings(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);
        abort_unless($schoolId && School::whereKey($schoolId)->exists(), 422, 'Satuan pendidikan tidak ditemukan.');

        $validated = $request->validate([
            'paper_size' => 'required|in:a4,folio,letter',
            'margin_top' => 'required|numeric|min:0|max:50',
            'margin_right' => 'required|numeric|min:0|max:50',
            'margin_bottom' => 'required|numeric|min:0|max:50',
            'margin_left' => 'required|numeric|min:0|max:50',
            'font_family' => 'required|in:Times New Roman,Arial,Georgia,Bookman Old Style,Tahoma',
            'font_size' => 'required|numeric|min:6|max:24',
            'line_height' => 'required|numeric|min:1|max:2.5',
            'title' => 'required|string|max:100',
            'number_prefix' => 'nullable|string|max:100',
            'curriculum' => 'required|string|max:100',
            'criteria_text' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'signer_jabatan' => 'required|string|max:100',
            'signer_name' => 'nullable|string|max:150',
            'signer_nip' => 'nullable|string|max:50',
            'group_labels' => 'nullable|array',
            'group_labels.*' => 'nullable|string|max:100',
            'rows' => 'nullable|array',
            'rows.*.id' => 'nullable|uuid',
            'rows.*.subject_id' => 'nullable|uuid|exists:subjects,id',
            'rows.*.display_name' => 'required|string|max:150',
            'rows.*.group_key' => 'required|in:umum,kejuruan,pilihan,muatan_lokal',
            'rows.*.sort_order' => 'nullable|integer|min:0|max:999',
        ], [
            'required' => ':attribute wajib diisi.',
            'numeric' => ':attribute harus berupa angka.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'in' => ':attribute yang dipilih tidak valid.',
            'uuid' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan.',
        ], [
            'paper_size' => 'ukuran kertas',
            'margin_top' => 'margin atas',
            'margin_right' => 'margin kanan',
            'margin_bottom' => 'margin bawah',
            'margin_left' => 'margin kiri',
            'font_family' => 'jenis font',
            'font_size' => 'ukuran font',
            'line_height' => 'jarak baris',
            'title' => 'judul dokumen',
            'number_prefix' => 'nomor surat',
            'curriculum' => 'kurikulum',
            'criteria_text' => 'kalimat kriteria kelulusan',
            'city' => 'kota penerbitan',
            'signer_jabatan' => 'jabatan penanda tangan',
            'signer_name' => 'nama penanda tangan',
            'signer_nip' => 'NIP penanda tangan',
            'rows' => 'daftar mata pelajaran',
            'rows.*.display_name' => 'nama mata pelajaran',
            'rows.*.group_key' => 'kelompok mata pelajaran',
            'rows.*.sort_order' => 'urutan mata pelajaran',
        ]);

        // Checkbox "tampil" tidak masuk $validated, jadi ambil dari input mentah.
        $rows = $request->input('rows', []);
        $this->ensureUniqueSubjects($rows);

        $layout = collect($validated)->only([
            'paper_size', 'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
            'font_family', 'font_size', 'line_height', 'title', 'number_prefix',
            'curriculum', 'criteria_text', 'city', 'signer_jabatan', 'signer_name', 'signer_nip',
        ])->all();

        $layout['show_kop'] = $request->boolean('show_kop');
        $layout['show_npsn'] = $request->boolean('show_npsn');
        $layout['show_ijazah'] = $request->boolean('show_ijazah');
        $layout['show_photo'] = $request->boolean('show_photo');
        $layout['show_nilai'] = $request->boolean('show_nilai');
        $layout['show_rata_rata'] = $request->boolean('show_rata_rata');
        $layout['show_signature'] = $request->boolean('show_signature');
        $layout['group_labels'] = $this->groupLabels($request->input('group_labels', []));
        $layout['updated_by'] = $request->user()?->id;

        DB::transaction(function () use ($schoolId, $layout, $rows) {
            GraduationCertificateSetting::updateOrCreate(['school_id' => $schoolId], $layout);

            $keep = [];
            foreach ($rows as $index => $row) {
                $data = [
                    'school_id' => $schoolId,
                    'subject_id' => $row['subject_id'] ?? null,
                    'group_key' => $row['group_key'],
                    'display_name' => $row['display_name'],
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                    'is_visible' => filter_var($row['is_visible'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];

                $existing = ! empty($row['id'])
                    ? GraduationCertificateSubject::where('school_id', $schoolId)->whereKey($row['id'])->first()
                    : null;

                if ($existing) {
                    $existing->update($data);
                    $keep[] = $existing->id;

                    continue;
                }

                $keep[] = GraduationCertificateSubject::create($data)->id;
            }

            GraduationCertificateSubject::where('school_id', $schoolId)
                ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
                ->delete();
        });

        return back()->with('success', 'Format Surat Keterangan Lulus berhasil disimpan.');
    }

    /**
     * Tarik seluruh mata pelajaran aktif sekolah ke daftar SKL (tanpa duplikat).
     */
    public function importSubjects(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);
        abort_unless($schoolId, 422, 'Satuan pendidikan tidak ditemukan.');

        $existing = GraduationCertificateSubject::where('school_id', $schoolId)
            ->whereNotNull('subject_id')
            ->pluck('subject_id')
            ->all();

        $subjects = Subject::where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($existing !== [], fn ($q) => $q->whereNotIn('id', $existing))
            ->orderBy('name')
            ->get();

        $order = (int) GraduationCertificateSubject::where('school_id', $schoolId)->max('sort_order');

        DB::transaction(function () use ($subjects, $schoolId, &$order) {
            foreach ($subjects as $subject) {
                $order++;

                GraduationCertificateSubject::create([
                    'school_id' => $schoolId,
                    'subject_id' => $subject->id,
                    'group_key' => $subject->category === 'muatan_lokal' ? 'muatan_lokal' : 'umum',
                    'display_name' => $subject->name,
                    'sort_order' => $order,
                    'is_visible' => true,
                ]);
            }
        });

        return back()->with('success', $subjects->count().' mata pelajaran ditambahkan ke daftar SKL.');
    }

    /**
     * Cetak SKL satu santri (PDF).
     */
    public function print(Request $request, string $userId, string $santriUuid)
    {
        $student = Student::withoutGlobalScope('school_context')
            ->with(['school.city'])
            ->findOrFail($santriUuid);

        $contextSchoolId = $request->attributes->get('schoolContextId');
        abort_if($contextSchoolId && (string) $contextSchoolId !== (string) $student->school_id, 403, 'Akses ditolak.');

        $school = $student->school;
        abort_unless($school, 404, 'Data sekolah santri tidak ditemukan.');

        // SKL hanya untuk santri berstatus lulus.
        if ($student->status !== 'graduate') {
            return back()->with('error', 'Surat Keterangan Lulus hanya dapat dicetak untuk santri berstatus Lulus.');
        }

        $setting = GraduationCertificateSetting::forSchool($school->id);
        $documentMedia = app(DocumentVisualService::class)->viewData($school, 'skl');

        try {
            $verification = $this->issueVerification($student, $school, $setting);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $rows = GraduationCertificateSubject::where('school_id', $school->id)
            ->where('is_visible', true)
            ->ordered()
            ->get();

        $scores = $this->subjectAverages($student);
        $groups = $this->groupRows($rows, $scores, $setting);

        $html = view('students.graduation-certificate.pdf', [
            'student' => $student,
            'school' => $school,
            'setting' => $setting,
            'groups' => $groups,
            'average' => $this->averageOf($groups),
            'graduationYear' => $this->graduationYearLabel($student),
            'kompetensi' => $this->kompetensiName($student),
            'photo' => $this->photoDataUri($student),
            'documentMedia' => $documentMedia,
            'verification' => $verification,
        ])->render();

        try {
            $dompdf = new Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper($this->dompdfPaper($setting->paper_size));
            $dompdf->render();
        } catch (\Throwable $e) {
            Log::error('Gagal membuat PDF SKL: '.$e->getMessage());

            return back()->with('error', 'Dokumen gagal dibuat. Silakan coba lagi.');
        }

        $pdf = (string) $dompdf->output();

        if (! empty($verification)) {
            app(DocumentSigningService::class)->finalize($verification['document'], $pdf);
        }

        $filename = 'SKL-'.preg_replace('/[^A-Za-z0-9]+/', '-', $student->name).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Daftarkan SKL ke registry verifikasi publik.
     *
     * Mode visual tidak menerbitkan registry. Mode PAdES ditolak selama provider
     * PSrE belum siap agar tidak terbit dokumen yang tampak sah tanpa signature.
     *
     * @throws \RuntimeException
     */
    private function issueVerification(Student $student, School $school, GraduationCertificateSetting $setting): ?array
    {
        $mode = app(DocumentVisualService::class)->modeForPrinting($school, 'skl');

        if ($mode === 'visual') {
            return null;
        }

        $signers = [];
        $signerName = $setting->signer_name ?: $school->principal_name;

        if ($signerName) {
            $signers[] = [
                'name' => $signerName,
                'role' => $setting->signer_jabatan ?: 'Kepala Sekolah',
            ];
        }

        try {
            return app(DocumentSigningService::class)->issue([
                'document_type' => 'skl',
                'document_number' => $setting->number_prefix,
                'title' => $setting->title,
                'school_id' => $school->id,
                'subject_type' => 'student',
                'subject_id' => $student->id,
                'issued_by' => auth()->id(),
                'validation_mode' => $mode,
                'metadata' => [
                    'kind' => 'skl',
                    'graduation_year' => $student->graduation_year,
                ],
            ], $signers);
        } catch (\RuntimeException $e) {
            // Termasuk penolakan PAdES: jangan lanjut mencetak.
            throw $e;
        } catch (\Throwable $e) {
            // Registry sedang bermasalah: dokumen tetap bisa dicetak dengan TTD/stempel.
            Log::warning('Gagal menerbitkan registry SKL: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Cetak SKL massal (satu PDF, satu halaman per santri).
     *
     * Menerima id data PD Keluar (menu Lulus) maupun id santri, sehingga bisa
     * dipakai dari daftar Lulus maupun dari daftar santri.
     */
    public function bulkPrint(Request $request, string $userId)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ], [
            'required' => 'Pilih minimal satu santri untuk dicetak.',
            'min' => 'Pilih minimal satu santri untuk dicetak.',
        ], [
            'ids' => 'santri',
        ]);

        $schoolContextId = $request->attributes->get('schoolContextId');

        $studentIds = StudentMutationOut::query()
            ->whereIn('id', $data['ids'])
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Bila yang dikirim ternyata id santri (bukan id data PD Keluar).
        if ($studentIds === []) {
            $studentIds = $data['ids'];
        }

        $students = Student::withoutGlobalScope('school_context')
            ->with(['school.city'])
            ->whereIn('id', $studentIds)
            ->where('status', 'graduate')
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->orderBy('name')
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada santri berstatus Lulus pada data yang dipilih.');
        }

        $settings = [];
        $rowsBySchool = [];
        $mediaBySchool = [];
        $verifications = [];

        try {
            $pages = $students->map(function (Student $student) use (&$settings, &$rowsBySchool, &$mediaBySchool, &$verifications) {
                $schoolId = $student->school_id;
                $setting = $settings[$schoolId] ??= GraduationCertificateSetting::forSchool($schoolId);
                $rows = $rowsBySchool[$schoolId] ??= GraduationCertificateSubject::where('school_id', $schoolId)
                    ->where('is_visible', true)
                    ->ordered()
                    ->get();
                $media = $mediaBySchool[$schoolId] ??= app(DocumentVisualService::class)->viewData($student->school, 'skl');
                $verification = $this->issueVerification($student, $student->school, $setting);

                if (! empty($verification)) {
                    $verifications[] = $verification;
                }

                $scores = $this->subjectAverages($student);
                $groups = $this->groupRows($rows, $scores, $setting);

                return [
                    'student' => $student,
                    'school' => $student->school,
                    'setting' => $setting,
                    'groups' => $groups,
                    'average' => $this->averageOf($groups),
                    'graduationYear' => $this->graduationYearLabel($student),
                    'kompetensi' => $this->kompetensiName($student),
                    'photo' => $this->photoDataUri($student),
                    'documentMedia' => $media,
                    'verification' => $verification,
                ];
            })->all();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $html = view('students.graduation-certificate.bulk-pdf', [
            'setting' => $settings[$students->first()->school_id],
            'pages' => $pages,
        ])->render();

        try {
            $dompdf = new Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper($this->dompdfPaper($settings[$students->first()->school_id]->paper_size));
            $dompdf->render();
        } catch (\Throwable $e) {
            Log::error('Gagal membuat PDF SKL massal: '.$e->getMessage());

            return back()->with('error', 'Dokumen gagal dibuat. Silakan coba lagi.');
        }

        $pdf = (string) $dompdf->output();

        foreach ($verifications as $verification) {
            app(DocumentSigningService::class)->finalize($verification['document'], $pdf);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="SKL-Massal.pdf"',
        ]);
    }

    /**
     * Foto santri sebagai data URI agar bisa dicetak Dompdf tanpa akses file.
     */
    private function photoDataUri(Student $student): ?string
    {
        $path = $student->photo_path;

        if (! $path) {
            return null;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($path)) {
                return null;
            }

            $mime = $disk->mimeType($path) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
        } catch (\Throwable $e) {
            Log::warning('Foto santri gagal dibaca untuk SKL: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Kelompokkan mapel sesuai urutan kelompok, sekaligus menempelkan nilai.
     *
     * @return array<int, array{key: string, label: string, items: array<int, array{name: string, score: ?float}>}>
     */
    private function groupRows($rows, array $scores, GraduationCertificateSetting $setting): array
    {
        $result = [];
        foreach (GraduationCertificateSetting::GROUPS as $key => $defaultLabel) {
            $items = $rows->where('group_key', $key)->values();
            if ($items->isEmpty()) {
                continue;
            }

            $result[] = [
                'key' => $key,
                'label' => $setting->groupLabel($key) ?: $defaultLabel,
                'items' => $items->map(fn ($row) => [
                    'name' => $row->display_name,
                    'score' => $row->subject_id && isset($scores[$row->subject_id])
                        ? round((float) $scores[$row->subject_id], 2)
                        : null,
                ])->all(),
            ];
        }

        return $result;
    }

    private function averageOf(array $groups): ?float
    {
        $values = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                if ($item['score'] !== null) {
                    $values[] = $item['score'];
                }
            }
        }

        return $values === [] ? null : round(array_sum($values) / count($values), 2);
    }

    /**
     * Rata-rata nilai akhir (nr_final) per mata pelajaran pada tahun ajaran kelulusan.
     *
     * @return array<string, float>
     */
    private function subjectAverages(Student $student): array
    {
        $query = DB::table('admin_nilai_sumatif as n')
            ->join('teacher_admin_books as b', 'b.id', '=', 'n.admin_book_id')
            ->where('n.student_id', $student->id)
            ->whereNotNull('n.nr_final');

        $yearIds = $this->graduationAcademicYearIds($student);
        if ($yearIds !== []) {
            $query->whereIn('b.academic_year_id', $yearIds);
        }

        return $query
            ->groupBy('b.subject_id')
            ->selectRaw('b.subject_id as subject_id, AVG(n.nr_final) as nilai')
            ->pluck('nilai', 'subject_id')
            ->map(fn ($value) => round((float) $value, 2))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function graduationAcademicYearIds(Student $student): array
    {
        if ($student->graduation_year) {
            $ids = AcademicYear::where('name', 'like', '%'.$student->graduation_year.'%')->pluck('id')->all();
            if ($ids !== []) {
                return $ids;
            }
        }

        return DB::table('student_class_histories')
            ->where('student_id', $student->id)
            ->distinct()
            ->pluck('academic_year_id')
            ->all();
    }

    /**
     * Nama rombel terakhir santri, dipakai sebagai Program/Konsentrasi Keahlian.
     */
    private function kompetensiName(Student $student): ?string
    {
        $name = DB::table('student_class_histories as h')
            ->join('study_groups as g', 'g.id', '=', 'h.study_group_id')
            ->where('h.student_id', $student->id)
            ->orderByDesc('h.created_at')
            ->value('g.name');

        return $name ?: null;
    }

    private function graduationYearLabel(Student $student): string
    {
        if ($student->graduation_year) {
            return (string) $student->graduation_year;
        }

        $year = $this->graduationAcademicYearIds($student);

        return $year === [] ? '-' : (string) AcademicYear::whereKey($year[0])->value('name');
    }

    private function resolveSchoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId')
            ?: $request->input('school_id')
            ?: $request->query('school_id')
            ?: School::orderBy('name')->value('id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function ensureUniqueSubjects(array $rows): void
    {
        $ids = collect($rows)->pluck('subject_id')->filter()->all();

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'rows' => 'Ada mata pelajaran yang tercantum lebih dari satu kali.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    private function groupLabels(array $input): array
    {
        $labels = [];
        foreach (GraduationCertificateSetting::GROUPS as $key => $default) {
            $value = trim((string) ($input[$key] ?? ''));
            $labels[$key] = $value !== '' ? $value : $default;
        }

        return $labels;
    }

    private function dompdfPaper(string $paper): string
    {
        return match ($paper) {
            'folio' => 'folio',
            'letter' => 'letter',
            default => 'A4',
        };
    }
}
