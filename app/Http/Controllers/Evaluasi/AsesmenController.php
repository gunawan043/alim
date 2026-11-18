<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\KisiKisiSoal;
use App\Models\LjkExam;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsesmenController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $base = KisiKisiSoal::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $query = (clone $base)
            ->with(['subject:id,name', 'gradeLevel:id,name,level', 'academicYear:id,name'])
            ->withCount('items');

        if ($request->filled('search')) {
            $query->where('judul', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('jenis_ujian')) {
            $query->where('jenis_ujian', $request->jenis_ujian);
        }
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'final') {
                $query->whereHas('pakets', fn ($q) => $q->where('is_published', true));
            } elseif ($status === 'draft') {
                $query->whereDoesntHave('pakets', fn ($q) => $q->where('is_published', true));
            }
        }

        $assessments = $query
            ->withCount(['items', 'pakets'])
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        // Dokumen terakhir per kisi/paket untuk tombol aksi di tabel pusat.
        // Modul paket/LJK dapat belum tersedia pada instalasi yang belum
        // menjalankan migrasi evaluasi — jangan sampai membuat pusat asesmen 500.
        $assessmentIds = $assessments->getCollection()->pluck('id');
        $latestPakets = collect();
        $latestLjks = collect();

        try {
            $latestPakets = PaketSoal::query()
                ->whereIn('kisi_kisi_soal_id', $assessmentIds)
                ->withCount('items')
                ->orderByDesc('updated_at')
                ->get()
                ->unique('kisi_kisi_soal_id')
                ->keyBy('kisi_kisi_soal_id');

            $latestLjks = LjkExam::query()
                ->whereIn('paket_soal_id', $latestPakets->pluck('id'))
                ->orderByDesc('created_at')
                ->get()
                ->unique('paket_soal_id')
                ->keyBy('paket_soal_id');
        } catch (\Throwable $e) {
            Log::warning('Pusat asesmen: modul paket/LJK tidak dapat dibaca', [
                'error' => $e->getMessage(),
            ]);
        }

        $states = [];
        foreach ($assessments as $assessment) {
            try {
                $states[$assessment->id] = $this->assessmentState($assessment, $userId, $schoolId);
            } catch (\Throwable $e) {
                // Satu baris bermasalah tidak boleh menjatuhkan seluruh halaman.
                Log::warning('Pusat asesmen: gagal menghitung status asesmen', [
                    'kisi_id' => $assessment->id,
                    'error' => $e->getMessage(),
                ]);

                $states[$assessment->id] = $this->state(
                    'soal',
                    'Periksa Data',
                    'user.asesmen.show',
                    ['userId' => $userId, 'kisiUuid' => $assessment->id],
                    'warning',
                );
            }
        }

        $bankBase = BankSoal::query()->accessibleBy($userId, $schoolId);
        $paketBase = PaketSoal::query()
            ->when($schoolId, fn ($q) => $q->whereHas('kisiKisi', fn ($kisi) => $kisi->where('school_id', $schoolId)));
        $ljkBase = LjkExam::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $stats = [
            'total' => (clone $base)->count(),
            'banks' => 0,
            'pakets' => 0,
            'ljk' => 0,
            'final' => 0,
        ];

        try {
            $stats['banks'] = (clone $bankBase)->count();
            $stats['pakets'] = (clone $paketBase)->count();
            $stats['ljk'] = (clone $ljkBase)->count();
            $stats['final'] = (clone $base)->whereHas('pakets', fn ($q) => $q->where('is_published', true))->count();
        } catch (\Throwable $e) {
            Log::warning('Pusat asesmen: statistik opsional gagal dihitung', [
                'error' => $e->getMessage(),
            ]);
        }

        $banks = collect();
        $pakets = collect();
        $ljkExams = collect();

        try {
            $banks = (clone $bankBase)
                ->with(['subject:id,name', 'gradeLevel:id,name', 'academicYear:id,name'])
                ->withCount(['soal', 'soalApproved as approved_soal_count'])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();

            $pakets = (clone $paketBase)
                ->with(['kisiKisi.subject:id,name', 'kisiKisi.gradeLevel:id,name'])
                ->withCount('items')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();

            $ljkExams = (clone $ljkBase)
                ->with(['paketSoal:id,judul,kode_paket', 'subject:id,name', 'studyGroup:id,name'])
                ->withCount(['participants', 'sheets'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Pusat asesmen: daftar dokumen opsional gagal dibaca', [
                'error' => $e->getMessage(),
            ]);
        }

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('evalusi.asesmen.index', compact(
            'assessments', 'states', 'stats', 'academicYears', 'userId',
            'latestPakets', 'latestLjks', 'banks', 'pakets', 'ljkExams'
        ));
    }

    public function show(Request $request, string $userId, string $kisiUuid)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $kisi = KisiKisiSoal::with([
            'subject:id,name',
            'gradeLevel:id,name,level',
            'academicYear:id,name',
            'items.tujuanPembelajaran',
            'items.kisiKisi',
        ])->withCount('items')->findOrFail($kisiUuid);

        abort_unless(! $schoolId || (string) $kisi->school_id === (string) $schoolId, 403);

        $banks = BankSoal::query()
            ->accessibleBy($userId, $schoolId)
            ->where('subject_id', $kisi->subject_id)
            ->withCount(['soal as approved_soal_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderByDesc('updated_at')
            ->get();

        $pakets = PaketSoal::where('kisi_kisi_soal_id', $kisi->id)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        $paketIds = $pakets->pluck('id');
        $ljkExams = LjkExam::whereIn('paket_soal_id', $paketIds)
            ->with(['studyGroup:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $state = $this->assessmentState($kisi, $userId, $schoolId);
        $approvedSoal = (int) $banks->sum('approved_soal_count');
        $targetSoal = max((int) $kisi->items->sum('jumlah_soal'), (int) $kisi->total_soal_target);
        $latestPaket = $pakets->first();

        $steps = [
            ['key' => 'rencana', 'label' => 'Rencana', 'done' => true],
            ['key' => 'soal', 'label' => 'Soal', 'done' => $banks->isNotEmpty() && $approvedSoal >= max(1, $targetSoal)],
            ['key' => 'paket', 'label' => 'Paket', 'done' => $pakets->isNotEmpty()],
            ['key' => 'review', 'label' => 'Review', 'done' => $latestPaket && in_array($latestPaket->workflow_status, [PaketSoal::WORKFLOW_APPROVED, PaketSoal::WORKFLOW_PUBLISHED], true)],
            ['key' => 'cetak', 'label' => 'Cetak', 'done' => $latestPaket?->is_published ?? false],
            ['key' => 'ljk', 'label' => 'LJK & Nilai', 'done' => $ljkExams->contains(fn ($exam) => in_array($exam->status, [LjkExam::STATUS_FINAL, LjkExam::STATUS_TERSINKRON], true))],
        ];

        return view('evalusi.asesmen.show', compact(
            'kisi', 'banks', 'pakets', 'ljkExams', 'state', 'steps', 'approvedSoal', 'targetSoal', 'latestPaket', 'userId'
        ));
    }

    public function cetak(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = PaketSoal::query()
            ->with(['kisiKisi.subject:id,name', 'kisiKisi.gradeLevel:id,name'])
            ->withCount(['items', 'printJobs'])
            ->where(function ($q) {
                $q->where('is_published', true)
                    ->orWhereIn('workflow_status', [PaketSoal::WORKFLOW_APPROVED, PaketSoal::WORKFLOW_PUBLISHED]);
            })
            ->when($schoolId, fn ($q) => $q->whereHas('kisiKisi', fn ($kisi) => $kisi->where('school_id', $schoolId)))
            ->orderByDesc('updated_at');

        $pakets = $query->paginate(20)->withQueryString();

        return view('evalusi.asesmen.cetak', compact('pakets', 'userId'));
    }

    private function assessmentState(KisiKisiSoal $kisi, string $userId, ?string $schoolId): array
    {
        $banks = BankSoal::query()
            ->accessibleBy($userId, $schoolId)
            ->where('subject_id', $kisi->subject_id)
            ->get();

        $approvedSoal = $banks->isEmpty() ? 0 : Soal::whereIn('bank_soal_id', $banks->pluck('id'))
            ->where('status', 'approved')
            ->count();

        $targetSoal = max((int) $kisi->items()->sum('jumlah_soal'), (int) $kisi->total_soal_target);
        $pakets = PaketSoal::where('kisi_kisi_soal_id', $kisi->id)->orderByDesc('created_at')->get();
        $latestPaket = $pakets->first();
        $ljk = $latestPaket ? LjkExam::where('paket_soal_id', $latestPaket->id)->orderByDesc('created_at')->first() : null;

        if ($banks->isEmpty()) {
            return $this->state('soal', 'Buat Bank Soal', 'user.bank-soal.create', ['userId' => $userId], 'warning');
        }
        if ($targetSoal > 0 && $approvedSoal < $targetSoal) {
            return $this->state('soal', 'Lengkapi Soal', 'user.bank-soal.index', ['userId' => $userId], 'warning');
        }
        if (! $latestPaket) {
            return $this->state('paket', 'Buat Paket Soal', 'user.paket-soal.create', ['userId' => $userId, 'kisiKisiId' => $kisi->id], 'primary');
        }
        if ($latestPaket->workflow_status === PaketSoal::WORKFLOW_DRAFT) {
            return $this->state('paket', 'Lengkapi Paket', 'user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $latestPaket->id], 'primary');
        }
        if (in_array($latestPaket->workflow_status, [PaketSoal::WORKFLOW_REVIEW, PaketSoal::WORKFLOW_REVISI], true)) {
            return $this->state('review', 'Review Paket', 'user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $latestPaket->id], 'info');
        }
        if ($latestPaket->workflow_status === PaketSoal::WORKFLOW_APPROVED && ! $latestPaket->is_published) {
            return $this->state('review', 'Publish Paket', 'user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $latestPaket->id], 'success');
        }
        if ($latestPaket->is_published && ! $ljk) {
            return $this->state('cetak', 'Terbitkan LJK', 'user.ljk.create', ['userId' => $userId, 'paket_soal_id' => $latestPaket->id], 'success');
        }
        if ($ljk && ! in_array($ljk->status, [LjkExam::STATUS_FINAL, LjkExam::STATUS_TERSINKRON], true)) {
            return $this->state('ljk', 'Pindai & Verifikasi LJK', 'user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $ljk->id], 'success');
        }

        return $this->state('selesai', 'Lihat Hasil', 'user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $ljk?->id], 'success');
    }

    private function state(string $step, string $label, string $route, array $params, string $color): array
    {
        return compact('step', 'label', 'route', 'params', 'color');
    }
}
