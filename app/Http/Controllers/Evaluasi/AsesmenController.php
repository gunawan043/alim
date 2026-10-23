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

        $assessments = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $stats = [
            'total' => (clone $base)->count(),
            'no_bank' => 0,
            'paket' => 0,
            'final' => 0,
        ];
        $states = [];
        foreach ($assessments as $assessment) {
            $states[$assessment->id] = $this->assessmentState($assessment, $userId, $schoolId);
            if ($states[$assessment->id]['step'] === 'soal') $stats['no_bank']++;
            if (in_array($states[$assessment->id]['step'], ['paket', 'review'], true)) $stats['paket']++;
            if ($states[$assessment->id]['step'] === 'selesai') $stats['final']++;
        }

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('evalusi.asesmen.index', compact('assessments', 'states', 'stats', 'academicYears', 'userId'));
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
