<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\PaketSoal;
use App\Models\ReviewAssignment;
use App\Models\Soal;
use App\Services\Evaluasi\ReviewWorkflowService;
use Illuminate\Http\Request;

/**
 * Inbox review guru serumpun — menangani Soal dan Paket Soal.
 * Reviewer melihat metadata, CP/TP, kunci, pembahasan, hasil similarity,
 * dan soal historis yang mirip sebelum memberikan keputusan.
 */
class ReviewSoalController extends Controller
{
    public function __construct(
        private readonly ReviewWorkflowService $workflow,
    ) {}

    public function index(Request $request, string $userId)
    {
        $assignments = ReviewAssignment::query()
            ->with(['reviewer:id,name'])
            ->where('reviewer_id', $userId)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'revision' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();

        $soalIds = $assignments->where('reviewable_type', Soal::class)->pluck('reviewable_id');
        $paketIds = $assignments->where('reviewable_type', PaketSoal::class)->pluck('reviewable_id');

        $soals = Soal::with(['bankSoal.subject:id,name', 'bankSoal.gradeLevel:id,name', 'creator:id,name'])
            ->whereIn('id', $soalIds)->get()->keyBy('id');

        $pakets = PaketSoal::with(['kisiKisi.subject:id,name', 'kisiKisi.gradeLevel:id,name'])
            ->whereIn('id', $paketIds)->get()->keyBy('id');

        $assignments->each(function (ReviewAssignment $assignment) use ($soals, $pakets) {
            $assignment->setRelation(
                'reviewable',
                $assignment->reviewable_type === Soal::class
                    ? $soals->get($assignment->reviewable_id)
                    : $pakets->get($assignment->reviewable_id)
            );
        });

        $stats = [
            'pending' => $assignments->where('status', ReviewAssignment::STATUS_PENDING)->count(),
            'approved' => $assignments->where('status', ReviewAssignment::STATUS_APPROVED)->count(),
            'revision' => $assignments->where('status', ReviewAssignment::STATUS_REVISION)->count(),
        ];

        return view('evalusi.review.index', compact('userId', 'assignments', 'stats'));
    }

    public function show(Request $request, string $userId, string $assignmentId)
    {
        $assignment = ReviewAssignment::findOrFail($assignmentId);

        if ($assignment->reviewer_id !== $userId) {
            abort(403, 'Hanya reviewer yang ditetapkan yang dapat membuka dokumen ini.');
        }

        $reviewable = $assignment->reviewable;

        if (! $reviewable) {
            abort(404, 'Dokumen review tidak ditemukan.');
        }

        $progress = $this->workflow->progress($reviewable);
        $similarities = collect();

        if ($reviewable instanceof Soal) {
            $reviewable->load(['options', 'tujuanPembelajaran.capaianPembelajaran', 'bankSoal.subject', 'bankSoal.gradeLevel', 'creator:id,name']);
            $similarities = $reviewable->similarities()
                ->with(['comparedSoal.bankSoal.subject', 'comparedSoal.bankSoal.academicYear', 'comparedSoal.creator:id,name'])
                ->get();
        } elseif ($reviewable instanceof PaketSoal) {
            $reviewable->load(['items.soal.options', 'kisiKisi.subject', 'kisiKisi.gradeLevel', 'kisiKisi.academicYear']);
        }

        return view('evalusi.review.show', compact('userId', 'assignment', 'reviewable', 'progress', 'similarities'));
    }

    public function decide(Request $request, string $userId, string $assignmentId)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,revision',
            'note' => 'nullable|string|max:2000',
        ]);

        $assignment = ReviewAssignment::findOrFail($assignmentId);

        if ($assignment->reviewer_id !== $userId) {
            abort(403, 'Hanya reviewer yang ditetapkan yang dapat memberi keputusan.');
        }

        $reviewable = $assignment->reviewable;

        if (! $reviewable) {
            abort(404, 'Dokumen review tidak ditemukan.');
        }

        $this->workflow->decide($reviewable, $assignment->id, $request->user(), $validated['status'], $validated['note'] ?? null);

        $message = $validated['status'] === ReviewAssignment::STATUS_APPROVED
            ? 'Soal disetujui. Status final mengikuti keputusan seluruh reviewer.'
            : 'Permintaan perbaikan dikirim. Dokumen berstatus Perlu Perbaikan.';

        return redirect()->route('user.review-soal.index', ['userId' => $userId])->with('success', $message);
    }
}
