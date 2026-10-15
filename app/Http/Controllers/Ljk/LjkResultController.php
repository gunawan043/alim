<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ljk;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Http\Controllers\Controller;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScanAnswer;
use App\Services\Ljk\LjkAccess;
use App\Services\Ljk\LjkGradingService;
use App\Services\Ljk\LjkReviewService;
use App\Services\Ljk\LjkSyncService;
use App\Services\SumatifHarianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Hasil & verifikasi: daftar nilai, pemeriksaan jawaban meragukan,
 * koreksi guru (ber-audit), finalisasi, dan sinkronisasi ke nilai resmi.
 */
class LjkResultController extends Controller
{
    use AuthorizesAcademicScope;

    public function __construct(
        private readonly LjkGradingService $grading,
        private readonly LjkReviewService $review,
        private readonly LjkSyncService $sync,
        private readonly LjkAccess $access,
        private readonly SumatifHarianService $sumatif,
    ) {}

    public function index(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::with(['studyGroup:id,name', 'subject:id,name', 'adminBook'])->findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang melihat hasil ujian ini.');
        }

        $rows = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->with(['student:id,name,nis', 'sheet'])
            ->orderBy('nomor_peserta')
            ->get()
            ->map(function (LjkParticipant $participant) {
                $scan = $this->grading->latestScan($participant);
                $answers = $scan ? $scan->answers : collect();

                return [
                    'participant' => $participant,
                    'scan' => $scan,
                    'needs_review' => $answers->where('needs_review', true)->count(),
                    'manual' => $answers->where('status', LjkScanAnswer::MANUAL)->count(),
                    'terbaca' => $answers->whereIn('status', [LjkScanAnswer::TERBACA, LjkScanAnswer::KOSONG])->count(),
                    'ganda' => $answers->where('status', LjkScanAnswer::GANDA)->count(),
                    'meragukan' => $answers->where('status', LjkScanAnswer::MERAGUKAN)->count(),
                    'answers' => $answers,
                ];
            });

        $statistics = [
            'peserta' => $rows->count(),
            'dipindai' => $rows->filter(fn ($row) => $row['scan'] !== null)->count(),
            'perlu_review' => $rows->filter(fn ($row) => $row['needs_review'] > 0)->count(),
            'bersih' => $rows->filter(fn ($row) => $row['scan'] !== null && $row['needs_review'] === 0)->count(),
            'manual' => $rows->sum('manual'),
            'rata_nilai' => round((float) $rows->filter(fn ($row) => $row['participant']->nilai_akhir !== null)
                ->avg(fn ($row) => (float) $row['participant']->nilai_akhir), 2),
            'tertinggi' => $rows->filter(fn ($row) => $row['participant']->nilai_akhir !== null)
                ->max(fn ($row) => (float) $row['participant']->nilai_akhir),
            'terendah' => $rows->filter(fn ($row) => $row['participant']->nilai_akhir !== null)
                ->min(fn ($row) => (float) $row['participant']->nilai_akhir),
        ];

        // Filter daftar: semua | perlu pemeriksaan | bersih | belum dipindai.
        $filter = (string) $request->query('filter', 'semua');
        $participants = $rows
            ->when($filter === 'review', fn ($c) => $c->filter(fn ($row) => $row['needs_review'] > 0))
            ->when($filter === 'bersih', fn ($c) => $c->filter(fn ($row) => $row['scan'] !== null && $row['needs_review'] === 0))
            ->when($filter === 'belum', fn ($c) => $c->filter(fn ($row) => $row['scan'] === null))
            ->values();

        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $participants = $participants->filter(function ($row) use ($search) {
                return str_contains(mb_strtolower((string) $row['participant']->student?->name), mb_strtolower($search))
                    || str_contains((string) $row['participant']->nomor_peserta, $search);
            })->values();
        }

        $summary = $this->grading->reviewSummary($exam);
        $syncLogs = $exam->syncLogs()->with('actor:id,name')->latest()->limit(5)->get();

        $nilaiTarget = $exam->nilai_target ?? config("ljk.nilai_target.{$exam->jenis_ujian}", []);
        $targetLabel = $nilaiTarget['label'] ?? '-';
        $columns = $exam->adminBook ? $this->sumatif->columnsFor($exam->adminBook) : [];

        return view('ljk.hasil', compact(
            'exam', 'participants', 'statistics', 'summary', 'syncLogs',
            'targetLabel', 'columns', 'userId', 'filter', 'search'
        ));
    }

    public function participant(Request $request, string $userId, string $ljkUuid, string $participantUuid)
    {
        $exam = LjkExam::with(['studyGroup:id,name', 'subject:id,name'])->findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canReview($request->user(), $exam) && ! $this->access->canView($request->user(), $exam)) {
            abort(403);
        }

        $participant = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->with(['student:id,name,nis', 'sheet'])
            ->findOrFail($participantUuid);

        $scan = $this->grading->latestScan($participant);
        $answers = $scan ? $scan->answers()->with('corrections.corrector:id,name')->get() : collect();

        $mapping = collect($participant->mappingItems())->keyBy('no');
        $previousScans = $participant->id
            ? \App\Models\LjkScan::query()->where('participant_id', $participant->id)->latest()->limit(10)->get()
            : collect();

        return view('ljk.peserta', compact('exam', 'participant', 'scan', 'answers', 'mapping', 'previousScans', 'userId'));
    }

    public function review(Request $request, string $userId, string $ljkUuid, string $answerUuid)
    {
        $answer = LjkScanAnswer::with(['participant', 'scan.exam'])->findOrFail($answerUuid);
        $exam = $answer->scan?->exam;
        if (! $exam || (string) $exam->id !== (string) $ljkUuid) {
            abort(404);
        }
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canReview($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang memeriksa jawaban ini.');
        }

        $data = $request->validate([
            'action' => 'required|in:accept,correct',
            'status' => 'nullable|in:terbaca,kosong,ganda,meragukan,tidak_dapat_diproses,manual',
            'label' => 'nullable|string|max:4',
            'point_final' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            if ($data['action'] === 'accept') {
                $this->review->accept($answer, $request->user());
                $message = 'Jawaban diterima tanpa perubahan.';
            } else {
                if (empty($data['status'])) {
                    throw new RuntimeException('Tentukan status jawaban setelah koreksi.');
                }

                $this->review->correct($answer, [
                    'status' => $data['status'],
                    'label' => $data['label'] ?? null,
                    'point_final' => $data['point_final'] ?? null,
                ], $request->user(), $data['reason'] ?? null);

                $message = 'Koreksi tersimpan (riwayat audit dicatat).';
            }
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }

    public function finalize(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canReview($request->user(), $exam)) {
            abort(403);
        }

        $summary = $this->grading->reviewSummary($exam);

        if ($summary['scanned'] < $summary['sheets']) {
            return back()->with('error', 'Masih ada lembar yang belum dipindai ('.$summary['scanned'].'/'.$summary['sheets'].').');
        }

        if ($summary['needs_review'] > 0) {
            return back()->with('error', 'Masih ada '.$summary['needs_review'].' lembar yang memerlukan pemeriksaan guru.');
        }

        $exam->forceFill(['status' => LjkExam::STATUS_FINAL])->save();

        activity()->performedOn($exam)->causedBy($request->user())
            ->withProperties(['summary' => $summary])
            ->log('ljk.exam.finalized');

        return back()->with('success', 'Ujian ditandai final — siap disinkronkan ke nilai resmi.');
    }

    public function sync(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canSync($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang menyinkronkan nilai ujian ini.');
        }

        $data = $request->validate([
            'column_id' => 'nullable|string|max:60',
            'force' => 'nullable|boolean',
        ]);

        if ($exam->status !== LjkExam::STATUS_FINAL && $exam->status !== LjkExam::STATUS_TERSINKRON) {
            return back()->with('error', 'Finalkan ujian terlebih dahulu sebelum sinkronisasi.');
        }

        try {
            $result = $this->sync->sync(
                $exam,
                $request->user(),
                (bool) ($data['force'] ?? false),
                $data['column_id'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result['skipped']) {
            return back()->with('success', 'Hasil ini sudah pernah disinkronkan — tidak ada perubahan.');
        }

        $message = "Sinkronisasi berhasil: {$result['count']} peserta masuk ke nilai resmi.";
        if ($result['pending'] !== []) {
            $message .= ' Menunggu pemeriksaan: '.count($result['pending']).' peserta.';
        }

        return back()->with('success', $message);
    }

    /**
     * Ringkasan audit koreksi per ujian.
     */
    public function corrections(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403);
        }

        $rows = DB::table('ljk_answer_corrections as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.corrected_by')
            ->leftJoin('ljk_participants as p', 'p.id', '=', 'c.participant_id')
            ->leftJoin('students as s', 's.id', '=', 'p.student_id')
            ->where('c.ljk_exam_id', $exam->id)
            ->orderByDesc('c.corrected_at')
            ->limit(500)
            ->get([
                'c.id', 'c.nomor', 'c.before', 'c.after', 'c.reason', 'c.corrected_at',
                'u.name as actor', 's.name as student', 'p.nomor_peserta',
            ]);

        return response()->json(['success' => true, 'data' => ['corrections' => $rows]]);
    }
}
