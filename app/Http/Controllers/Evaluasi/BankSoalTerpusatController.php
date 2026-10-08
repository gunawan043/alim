<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalCloneLog;
use App\Models\SoalOption;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\Evaluasi\ContentHashEngine;
use App\Services\Evaluasi\SoalSimilarityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bank Soal Terpusat — repository soal lintas satuan pendidikan.
 *
 * "Serumpun" ditentukan oleh konteks akademik (mapel, jenjang, kelas/fase,
 * tahun ajaran, semester) — BUKAN school_id. Struktur organisasi hanya untuk
 * hak akses & workflow.
 */
class BankSoalTerpusatController extends Controller
{
    public function __construct(
        private readonly SoalSimilarityService $similarity,
        private readonly ContentHashEngine $hashEngine,
    ) {}

    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $mySubjectIds = $this->mySubjectIds($user);

        // ── Scope akses repository (terpusat + publik + internal sekolah + milik sendiri)
        $accessible = Soal::query()
            ->where(function ($q) use ($user, $schoolId) {
                $q->where('dibuat_oleh', $user->id)
                    ->orWhereHas('bankSoal', fn ($b) => $b->accessibleBy($user->id, $schoolId));
            });

        $baseQuery = (clone $accessible)
            ->with([
                'bankSoal:id,school_id,subject_id,jenjang,grade_level_id,academic_year_id,semester,nama,is_central,shared_scope',
                'bankSoal.school:id,name',
                'bankSoal.subject:id,name',
                'bankSoal.gradeLevel:id,name',
                'bankSoal.academicYear:id,name',
                'creator:id,name',
                'tujuanPembelajaran:id,kode_tp,deskripsi',
            ]);

        // ── Quick scope: saya / serumpun / terverifikasi / historis
        $scope = $request->input('scope');
        if ($scope === 'saya') {
            $baseQuery->where('dibuat_oleh', $user->id);
        } elseif ($scope === 'serumpun') {
            $baseQuery->where('dibuat_oleh', '<>', $user->id)
                ->when($mySubjectIds !== [], fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->whereIn('subject_id', $mySubjectIds)));
        } elseif ($scope === 'terverifikasi') {
            $baseQuery->where(function ($q) {
                $q->where('workflow_status', Soal::WORKFLOW_APPROVED)->orWhere('status', 'approved');
            });
        } elseif ($scope === 'historis') {
            $activeAy = AcademicYear::where('is_active', true)->value('id');
            $baseQuery->whereHas('bankSoal', function ($b) use ($activeAy) {
                $b->where(function ($q) use ($activeAy) {
                    if ($activeAy) {
                        $q->where('academic_year_id', '<>', $activeAy);
                    }
                });
            });
        }

        // ── Filter lengkap
        $baseQuery
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(fn ($q2) => $q2->where('pertanyaan', 'like', "%{$term}%")->orWhere('materi', 'like', "%{$term}%"));
            })
            ->when($request->filled('subject_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('subject_id', $request->subject_id)))
            ->when($request->filled('jenjang'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('jenjang', $request->jenjang)))
            ->when($request->filled('grade_level_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('grade_level_id', $request->grade_level_id)))
            ->when($request->filled('fase'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('fase', $request->fase)))
            ->when($request->filled('academic_year_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('academic_year_id', $request->academic_year_id)))
            ->when($request->filled('semester'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('semester', $request->semester)))
            ->when($request->filled('tipe_soal'), fn ($q) => $q->where('tipe_soal', $request->tipe_soal))
            ->when($request->filled('tingkat_kesulitan_estimasi'), fn ($q) => $q->where('tingkat_kesulitan_estimasi', $request->tingkat_kesulitan_estimasi))
            ->when($request->filled('materi'), fn ($q) => $q->where('materi', 'like', '%'.trim($request->materi).'%'))
            ->when($request->filled('tp_id'), fn ($q) => $q->where('tp_id', $request->tp_id))
            ->when($request->filled('workflow_status'), fn ($q) => $q->where('workflow_status', $request->workflow_status))
            ->when($request->filled('school_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('school_id', $request->school_id)))
            ->when($request->filled('dibuat_oleh'), fn ($q) => $q->where('dibuat_oleh', $request->dibuat_oleh))
            ->when($request->filled('jenis_asesmen'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('jenis_soal', $request->jenis_asesmen)));

        $soalList = $baseQuery->orderByDesc('created_at')->paginate(20)->withQueryString();

        // ── Statistik repository
        $stats = [
            'saya' => Soal::where('dibuat_oleh', $user->id)->count(),
            'serumpun' => (clone $accessible)
                ->where('dibuat_oleh', '<>', $user->id)
                ->when($mySubjectIds !== [], fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->whereIn('subject_id', $mySubjectIds)))
                ->count(),
            'terverifikasi' => (clone $accessible)
                ->where(fn ($q) => $q->where('workflow_status', Soal::WORKFLOW_APPROVED)->orWhere('status', 'approved'))
                ->count(),
            'historis' => (function () use ($accessible) {
                $activeAy = AcademicYear::where('is_active', true)->value('id');

                return (clone $accessible)->when($activeAy, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('academic_year_id', '<>', $activeAy)))->count();
            })(),
        ];

        // ── Referensi filter
        $subjects = Subject::orderBy('name')->get(['id', 'name']);
        $gradeLevels = GradeLevel::orderBy('level')->get(['id', 'name', 'fase']);
        $academicYears = AcademicYear::orderByDesc('start_date')->get(['id', 'name']);
        $schools = School::orderBy('name')->get(['id', 'name']);
        $jenjangOptions = $gradeLevels->pluck('fase')->filter()->unique()->values();

        return view('evalusi.bank-soal-terpusat.index', compact(
            'userId',
            'soalList',
            'stats',
            'subjects',
            'gradeLevels',
            'academicYears',
            'schools',
            'jenjangOptions',
            'scope'
        ));
    }

    /**
     * Gunakan soal historis sebagai DASAR — membuat turunan baru (draft),
     * soal asli tidak berubah, relasi derived_from_soal_id tercatat.
     */
    public function reuse(Request $request, string $userId, string $soalId)
    {
        $user = $request->user();
        $original = Soal::with('options')->findOrFail($soalId);

        // Derivative disimpan pada bank target (default: bank yang sama).
        $targetBankId = $request->input('target_bank_id', $original->bank_soal_id);
        $targetBank = BankSoal::findOrFail($targetBankId);

        $derivative = DB::transaction(function () use ($original, $targetBank, $user) {
            $correctTexts = $original->options
                ->where('is_correct', true)
                ->pluck('teks_opsi')
                ->all();

            $copy = Soal::create([
                'bank_soal_id' => $targetBank->id,
                'tp_id' => $original->tp_id,
                'materi' => $original->materi,
                'derived_from_soal_id' => $original->id,
                'tipe_soal' => $original->tipe_soal,
                'pertanyaan' => $original->pertanyaan,
                'pembahasan' => $original->pembahasan,
                'gambar_path' => $original->gambar_path,
                'audio_path' => $original->audio_path,
                'bobot_default' => $original->bobot_default,
                'tingkat_kesulitan_estimasi' => $original->tingkat_kesulitan_estimasi,
                'waktu_estimasi_menit' => $original->waktu_estimasi_menit,
                'tags' => $original->tags,
                'status' => 'draft',
                'workflow_status' => Soal::WORKFLOW_DRAFT,
                'dibuat_oleh' => $user->id,
                // Hash unik tidak boleh sama (unique index) — turunan tetap
                // terdeteksi mirip lewat shingles/text & relasi derived_from.
                'content_hash' => hash('sha256', ($original->content_hash ?? $original->id).'|deriv|'.now()->timestamp.'|'.mt_rand()),
                'shingles_hash' => $this->hashEngine->shinglesFromSoal($original->pertanyaan),
            ]);

            foreach ($original->options as $option) {
                SoalOption::create([
                    'soal_id' => $copy->id,
                    'label' => $option->label,
                    'teks_opsi' => $option->teks_opsi,
                    'gambar_path' => $option->gambar_path,
                    'is_correct' => (bool) $option->is_correct,
                    'urutan' => $option->urutan,
                ]);
            }

            SoalCloneLog::create([
                'soal_asli_id' => $original->id,
                'soal_clone_id' => $copy->id,
                'cloned_by' => $user->id,
                'cloned_at' => now(),
                'from_school_id' => $original->bankSoal?->school_id,
                'to_school_id' => $targetBank->school_id,
                'clone_type' => 'adapt',
                'notes' => 'Gunakan sebagai dasar — versi baru harus melalui review ulang.',
            ]);

            return $copy;
        });

        return redirect()
            ->route('user.soal.edit', [
                'userId' => $userId,
                'bankId' => $derivative->bank_soal_id,
                'id' => $derivative->id,
            ])
            ->with('success', 'Salinan turunan dibuat (draft). Soal asli tidak berubah — lengkapi lalu ajukan review.');
    }

    /**
     * Data perbandingan soal (comparison view) — JSON.
     */
    public function compare(string $userId, string $soalId, string $comparedId)
    {
        $soal = Soal::with('options')->findOrFail($soalId);
        $compared = Soal::with(['options', 'bankSoal.subject', 'bankSoal.academicYear', 'creator:id,name'])->findOrFail($comparedId);

        $result = $this->similarity->compare($soal, $compared);

        return response()->json([
            'score' => $result['score'],
            'level' => $result['level'],
            'soal' => [
                'id' => $soal->id,
                'pertanyaan' => $soal->pertanyaan,
                'pembahasan' => $soal->pembahasan,
                'options' => $soal->options->map(fn ($o) => ['label' => $o->label, 'teks' => $o->teks_opsi, 'correct' => (bool) $o->is_correct]),
            ],
            'compared' => [
                'id' => $compared->id,
                'pertanyaan' => $compared->pertanyaan,
                'pembahasan' => $compared->pembahasan,
                'options' => $compared->options->map(fn ($o) => ['label' => $o->label, 'teks' => $o->teks_opsi, 'correct' => (bool) $o->is_correct]),
                'subject' => $compared->bankSoal?->subject?->name,
                'academic_year' => $compared->bankSoal?->academicYear?->name,
                'pembuat' => $compared->creator?->name,
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function mySubjectIds($user): array
    {
        return TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();
    }
}
