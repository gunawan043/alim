<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScanAnswer;
use App\Models\LjkSyncLog;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\SumatifHarianService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sinkronisasi hasil LJK ke modul nilai resmi (admin_nilai_sumatif).
 *
 * Prinsip:
 * - Memakai jalur resmi `SumatifHarianService::upsertSumatif` (satu sumber
 *   aturan nilai), bukan menulis kolom nilai secara manual.
 * - Hanya peserta yang jawabannya sudah bersih (tanpa pemeriksaan tertunda)
 *   yang disinkronkan; sisanya dilaporkan.
 * - Idempotent: hash payload yang sama tidak disinkronkan dua kali.
 * - Menolak buku nilai yang terkunci / tahun arsip (aturan yang sama dengan
 *   input nilai manual).
 */
class LjkSyncService
{
    public function __construct(
        private readonly LjkGradingService $grading,
        private readonly LjkExamService $exams,
        private readonly SumatifHarianService $sumatif,
    ) {}

    /**
     * Tulis nilai satu peserta lewat jalur resmi + provenans LJK.
     */
    public function pushScore(
        TeacherAdminBook $book,
        LjkExam $exam,
        string $studentId,
        float $nilai,
        string $mode,
        ?string $columnId = null,
    ): void {
        $input = match ($mode) {
            'sts_murni' => ['sts_murni' => $nilai],
            'sas_murni' => ['sas_murni' => $nilai],
            default => ['sh' => [(string) $columnId => $nilai]],
        };

        $row = $this->sumatif->upsertSumatif($book, $studentId, $input);

        $row->forceFill([
            'paket_soal_id' => $exam->paket_soal_id,
            'ljk_exam_id' => $exam->id,
        ])->save();
    }

    /**
     * Tentukan mode & kolom nilai tujuan (STS murni / SAS murni / kolom sumatif).
     *
     * @return array{0: string, 1: ?string}
     */
    private function resolveTarget(LjkExam $exam, TeacherAdminBook $book, ?string $columnId, bool $strict = false): array
    {
        $target = $exam->nilai_target ?? config("ljk.nilai_target.{$exam->jenis_ujian}", ['mode' => 'sumatif_column']);
        $mode = (string) ($target['mode'] ?? 'sumatif_column');
        $columnId = $columnId ?? ($target['column_id'] ?? null);

        if ($mode === 'sumatif_column') {
            $columns = $this->sumatif->columnsFor($book);
            $ids = array_map(static fn ($c) => (string) $c['id'], $columns);

            if ($columnId === null || ! in_array((string) $columnId, $ids, true)) {
                if ($strict) {
                    throw new RuntimeException('Kolom sumatif harian tujuan tidak ditemukan pada buku nilai ini.');
                }

                // Auto-sync: pakai kolom pertama yang tersedia sebagai tujuan.
                $columnId = $ids[0] ?? null;

                if ($columnId === null) {
                    throw new RuntimeException('Buku nilai belum memiliki kolom sumatif harian.');
                }
            }
        }

        return [$mode, $columnId];
    }

    /**
     * AUTO-SINKRON satu peserta setelah pemindaian / koreksi.
     *
     * Nilai langsung masuk ke kolom resmi (STS murni / SAS murni / sumatif
     * harian) HANYA bila:
     *  - pelajaran ini tertaut ke buku nilai dan buku belum dikunci;
     *  - lembar terakhir sudah dipindai dan TIDAK ada nomor yang menunggu
     *    pemeriksaan (identitas valid, tidak ada ganda/meragukan/manual);
     *  - naskah paket masih sesuai versi saat LJK diterbitkan.
     *
     * Idempoten: nilai yang sama tidak ditulis dua kali (hash per peserta).
     *
     * @return array{written: bool, reason: string, mode: ?string}
     */
    public function autoSyncParticipant(LjkExam $exam, LjkParticipant $participant, ?User $actor = null): array
    {
        $exam->refresh();

        if (! $exam->admin_book_id) {
            return ['written' => false, 'reason' => 'no_book', 'mode' => null];
        }

        /** @var TeacherAdminBook|null $book */
        $book = TeacherAdminBook::find($exam->admin_book_id);

        if (! $book) {
            return ['written' => false, 'reason' => 'no_book', 'mode' => null];
        }

        if ($book->isLocked()) {
            return ['written' => false, 'reason' => 'book_locked', 'mode' => null];
        }

        if ($exam->status === LjkExam::STATUS_DIBATALKAN) {
            return ['written' => false, 'reason' => 'cancelled', 'mode' => null];
        }

        $scan = $this->grading->latestScan($participant);

        if (! $scan) {
            return ['written' => false, 'reason' => 'no_scan', 'mode' => null];
        }

        $pending = LjkScanAnswer::query()
            ->where('ljk_scan_id', $scan->id)
            ->where('needs_review', true)
            ->exists();

        if ($pending) {
            return ['written' => false, 'reason' => 'pending_review', 'mode' => null];
        }

        if ($participant->nilai_akhir === null) {
            return ['written' => false, 'reason' => 'no_score', 'mode' => null];
        }

        // Kunci penilaian harus dari versi paket yang benar.
        if (! $this->exams->verifyPaketDigest($exam)['ok']) {
            return ['written' => false, 'reason' => 'paket_changed', 'mode' => null];
        }

        try {
            [$mode, $columnId] = $this->resolveTarget($exam, $book, null);
        } catch (RuntimeException $e) {
            return ['written' => false, 'reason' => 'no_target_column', 'mode' => null];
        }

        $payloadHash = hash('sha256', json_encode([
            'auto' => true,
            'exam' => $exam->id,
            'student' => $participant->student_id,
            'mode' => $mode,
            'column' => $columnId,
            'score' => (float) $participant->nilai_akhir,
        ], JSON_UNESCAPED_SLASHES));

        if (LjkSyncLog::query()->where('ljk_exam_id', $exam->id)->where('payload_hash', $payloadHash)->exists()) {
            return ['written' => false, 'reason' => 'already_synced', 'mode' => $mode];
        }

        DB::transaction(function () use ($book, $exam, $participant, $mode, $columnId) {
            $this->pushScore($book, $exam, (string) $participant->student_id, (float) $participant->nilai_akhir, $mode, $columnId);
        });

        LjkSyncLog::create([
            'ljk_exam_id' => $exam->id,
            'admin_book_id' => $book->id,
            'actor_id' => $actor?->id,
            'action' => 'auto',
            'students_count' => 1,
            'success_count' => 1,
            'skipped_count' => 0,
            'payload_hash' => $payloadHash,
            'meta' => [
                'mode' => $mode,
                'column_id' => $columnId,
                'auto' => true,
                'participant_id' => $participant->id,
                'student_id' => $participant->student_id,
                'score' => (float) $participant->nilai_akhir,
            ],
        ]);

        activity()
            ->performedOn($exam)
            ->causedBy($actor)
            ->withProperties([
                'participant' => $participant->nomor_peserta,
                'score' => (float) $participant->nilai_akhir,
                'mode' => $mode,
                'column_id' => $columnId,
            ])
            ->log('ljk.exam.autosynced');

        return ['written' => true, 'reason' => 'ok', 'mode' => $mode];
    }

    /**
     * @return array{skipped: bool, count: int, pending: array<int, string>, log: ?LjkSyncLog, book_locked: bool}
     */
    public function sync(LjkExam $exam, User $actor, bool $force = false, ?string $columnId = null): array
    {
        if (! $exam->admin_book_id) {
            throw new RuntimeException('Ujian belum ditautkan ke buku nilai — nilai tidak dapat disinkronkan.');
        }

        /** @var TeacherAdminBook $book */
        $book = TeacherAdminBook::findOrFail($exam->admin_book_id);

        if ($book->isLocked()) {
            throw new RuntimeException('Buku nilai sudah dikunci final — hubungi tim kurikulum untuk membuka sebelum sinkronisasi.');
        }

        // Deteksi perubahan naskah paket setelah lembar diterbitkan.
        $digest = $this->exams->verifyPaketDigest($exam);
        if (! $digest['ok'] && ! $force) {
            throw new RuntimeException('Naskah Paket Soal berubah setelah LJK diterbitkan. Periksa kembali atau minta kurikulum melakukan sinkronisasi paksa.');
        }

        [$mode, $columnId] = $this->resolveTarget($exam, $book, $columnId, true);

        $participants = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->get();

        $ready = [];
        $pending = [];

        foreach ($participants as $participant) {
            $scan = $this->grading->latestScan($participant);

            if (! $scan) {
                $pending[] = $participant->nomor_peserta;

                continue;
            }

            $hasReview = LjkScanAnswer::query()
                ->where('ljk_scan_id', $scan->id)
                ->where('needs_review', true)
                ->exists();

            if ($hasReview) {
                $pending[] = $participant->nomor_peserta;

                continue;
            }

            if ($participant->nilai_akhir === null) {
                $pending[] = $participant->nomor_peserta;

                continue;
            }

            $ready[$participant->student_id] = (float) $participant->nilai_akhir;
        }

        if ($ready === []) {
            throw new RuntimeException('Belum ada peserta dengan hasil final yang siap disinkronkan.');
        }

        ksort($ready);

        $payloadHash = hash('sha256', json_encode([
            'exam' => $exam->id,
            'book' => $book->id,
            'mode' => $mode,
            'column' => $columnId,
            'scores' => $ready,
        ], JSON_UNESCAPED_SLASHES));

        $existing = LjkSyncLog::query()
            ->where('ljk_exam_id', $exam->id)
            ->where('payload_hash', $payloadHash)
            ->first();

        if ($existing) {
            return [
                'skipped' => true,
                'count' => (int) $existing->success_count,
                'pending' => $pending,
                'log' => $existing,
                'book_locked' => false,
            ];
        }

        $success = 0;

        DB::transaction(function () use ($ready, $book, $mode, $columnId, $exam, &$success) {
            foreach ($ready as $studentId => $nilai) {
                $this->pushScore($book, $exam, (string) $studentId, (float) $nilai, $mode, $columnId);
                $success++;
            }
        });

        $log = LjkSyncLog::create([
            'ljk_exam_id' => $exam->id,
            'admin_book_id' => $book->id,
            'actor_id' => $actor->id,
            'action' => $exam->status === LjkExam::STATUS_TERSINKRON ? LjkSyncLog::ACTION_RESYNC : LjkSyncLog::ACTION_SYNC,
            'students_count' => count($ready) + count($pending),
            'success_count' => $success,
            'skipped_count' => count($pending),
            'payload_hash' => $payloadHash,
            'meta' => [
                'mode' => $mode,
                'column_id' => $columnId,
                'pending_nomor_peserta' => $pending,
                'digest_ok' => $digest['ok'],
                'forced' => $force && ! $digest['ok'],
                'nilai' => $ready,
            ],
        ]);

        if ($pending === [] && $exam->status !== LjkExam::STATUS_TERSINKRON) {
            $exam->forceFill(['status' => LjkExam::STATUS_TERSINKRON])->save();
        }

        activity()
            ->performedOn($exam)
            ->causedBy($actor)
            ->withProperties([
                'sync_log' => $log->id,
                'success' => $success,
                'pending' => count($pending),
                'mode' => $mode,
            ])
            ->log('ljk.exam.synced');

        return [
            'skipped' => false,
            'count' => $success,
            'pending' => $pending,
            'log' => $log,
            'book_locked' => false,
        ];
    }
}
