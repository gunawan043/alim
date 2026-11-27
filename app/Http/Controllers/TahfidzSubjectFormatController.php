<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Subject;
use App\Services\TahfidzAccess;
use App\Services\TahfidzGradebookCalculator;
use App\Services\TahfidzSubjectResolver;
use App\Services\TahfidzWeightService;
use Illuminate\Http\Request;

/**
 * Verifikasi format penilaian mapel tahfidz.
 *
 * Format penilaian menentukan apakah sebuah mapel dinilai dengan format
 * umum (RS/STS/SAS) atau tahfidz (Ziyadah/Rabt/STS/SAS). Penanda disimpan
 * eksplisit di `subjects.penilaian_format`; halaman ini adalah satu-satunya
 * tempat admin mengubahnya, supaya tidak ada mapel yang formatnya bergeser
 * karena nama mapel ditulis berbeda.
 */
class TahfidzSubjectFormatController extends Controller
{
    public function __construct(
        private TahfidzSubjectResolver $resolver,
        private TahfidzAccess $access,
        private TahfidzWeightService $weights,
    ) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $subjects = Subject::with(['school:id,name', 'subjectGroup:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        $tahfidz = [];
        $umumMirip = [];

        foreach ($subjects as $subject) {
            $isTahfidz = $this->resolver->isTahfidz($subject);

            if ($isTahfidz) {
                $tahfidz[] = $subject;
                continue;
            }

            // Mapel berformat umum yang namanya mirip tahfidz → sarankan.
            $suggestion = $this->resolver->suggestByName($subject);
            if ($suggestion['score'] !== null || $suggestion['exact']) {
                $subject->setAttribute('saran_skor', $suggestion['score']);
                $umumMirip[] = $subject;
            }
        }

        // Bobot NR Final: milik departemen, per sekolah × tahun ajaran.
        $academicYears = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'semester', 'is_active']);
        $academicYearId = $request->input('academic_year_id')
            ?? $academicYears->firstWhere('is_active', true)?->id
            ?? $academicYears->first()?->id;

        $stored = $this->weights->storedFor($schoolId, $academicYearId);

        return view('tahfidz.format-verifikasi', [
            'userId' => $userId,
            'tahfidzSubjects' => $tahfidz,
            'suggestedSubjects' => $umumMirip,
            'schoolName' => $schoolId ? $subjects->first()?->school?->name : null,
            'academicYears' => $academicYears,
            'academicYearId' => $academicYearId,
            'weightColumns' => TahfidzGradebookCalculator::KEYS,
            'weightLabels' => \App\Services\TahfidzLegerColumns::COLUMNS,
            'weightGanjil' => $stored['ganjil'] ?? $this->weights->defaultsFor('ganjil'),
            'weightGenap' => $stored['genap'] ?? $this->weights->defaultsFor('genap'),
            'weightSaved' => $stored['ganjil'] !== null || $stored['genap'] !== null,
            'canEditWeights' => $this->access->canManageCurriculum($request->user()),
        ]);
    }

    /**
     * Simpan bobot NR Final tahfidz (semester ganjil & genap sekaligus).
     *
     * Hanya Departemen Tahfidz (atau super admin). Setelah tersimpan, seluruh
     * buku halaqah di sekolah itu memakai angka ini dan NR Final dihitung ulang.
     */
    public function storeWeights(Request $request, string $userId)
    {
        abort_unless($this->access->canManageCurriculum($request->user()), 403,
            'Hanya Departemen Tahfidz yang dapat mengatur bobot nilai tahfidz.');

        $schoolId = $request->attributes->get('schoolContextId');
        abort_if(! $schoolId, 422, 'Pilih satuan pendidikan terlebih dahulu sebelum mengatur bobot.');

        $data = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'ganjil' => 'array',
            'ganjil.*' => 'nullable|numeric|min:0|max:100',
            'genap' => 'array',
            'genap.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $errors = $this->weights->save(
            $schoolId,
            $data['academic_year_id'],
            (array) ($data['ganjil'] ?? []),
            (array) ($data['genap'] ?? []),
            $request->user()?->id,
        );

        $redirect = redirect()->route('user.tahfidz.format.index', [
            'userId' => $userId,
            'academic_year_id' => $data['academic_year_id'],
        ]);

        if ($errors !== []) {
            return $redirect->with('error', 'Bobot tidak disimpan: '.implode(' ', $errors));
        }

        return $redirect->with('success',
            'Bobot NR Final tahfidz tersimpan. NR Final seluruh buku halaqah di sekolah ini sudah dihitung ulang.');
    }

    public function update(Request $request, string $userId)
    {
        $data = $request->validate([
            'formats' => 'array',
            'formats.*' => 'in:umum,tahfidz',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        $changed = 0;

        foreach ($data['formats'] ?? [] as $subjectId => $format) {
            $subject = Subject::find($subjectId);

            if (! $subject) {
                continue;
            }

            if ($schoolId && $subject->school_id !== $schoolId) {
                continue;
            }

            if ($subject->penilaian_format === $format) {
                continue;
            }

            $subject->update(['penilaian_format' => $format]);
            $changed++;
        }

        $message = $changed > 0
            ? "Format penilaian {$changed} mapel diperbarui."
            : 'Tidak ada perubahan format penilaian.';

        // Nilai buku yang sudah tersimpan tidak diubah — hanya cara mapel
        // dinilai ke depan. Beri tahu agar admin tidak mengira nilai lama
        // ikut berubah.
        if ($changed > 0) {
            $message .= ' Nilai yang sudah tersimpan tidak diubah; periksa kembali buku nilai mapel tersebut.';
        }

        return redirect()->route('user.tahfidz.format.index', ['userId' => $userId])
            ->with('success', $message);
    }
}
