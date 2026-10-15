<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkSheet;
use App\Models\LjkTemplate;
use App\Models\PaketSoal;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orkestrasi ujian LJK: dari Paket Soal final → peserta → lembar siap cetak.
 *
 * Sumber kebenaran soal tetap Paket Soal (`konten_snapshot` pada saat
 * publish). Yang disimpan di sini hanya referensi + sidik jari versi.
 */
class LjkExamService
{
    public function __construct(
        private readonly LjkTemplateService $templates,
        private readonly LjkSheetService $sheets,
    ) {}

    /**
     * @param  array{judul:string, paket_soal_id:string, admin_book_id?:?string, study_group_id?:?string, jenis_ujian?:string, tanggal?:?string, semester?:?string, nilai_target?:?array, instruksi?:?string, allow_draft?:bool}  $data
     */
    public function createExam(array $data, User $actor): LjkExam
    {
        $paket = PaketSoal::with(['items.soal.options'])->findOrFail($data['paket_soal_id']);

        if (! $paket->isFinal() && ! ($data['allow_draft'] ?? false)) {
            throw new RuntimeException('Paket soal harus sudah final (disetujui & dipublikasikan) sebelum LJK diterbitkan.');
        }

        $book = null;
        if (! empty($data['admin_book_id'])) {
            $book = TeacherAdminBook::findOrFail($data['admin_book_id']);
        }

        $jenis = (string) ($data['jenis_ujian'] ?? 'ulangan_harian');
        if (! in_array($jenis, LjkExam::JENIS_UJIAN, true)) {
            throw new RuntimeException('Jenis ujian tidak dikenal.');
        }

        $items = $paket->items()->with(['soal.options'])->orderBy('urutan')->get();
        if ($items->isEmpty()) {
            throw new RuntimeException('Paket soal tidak memiliki butir.');
        }

        // Spesifikasi butir (tanpa menyalin isi soal).
        $specs = $this->templates->specsFromItems($items);
        $template = $this->templates->registerTemplate($specs, $actor->id);

        $totalWeight = 0.0;
        $manualCount = 0;
        foreach ($specs as $spec) {
            if (in_array($spec['jenis'], config('ljk.grading.manual_types', []), true)) {
                $manualCount++;
            } else {
                $totalWeight += $spec['bobot'];
            }
        }

        return DB::transaction(function () use ($data, $paket, $book, $jenis, $items, $template, $actor, $totalWeight, $manualCount) {
            $exam = LjkExam::create([
                'school_id' => $data['school_id'] ?? $book?->school_id ?? $paket->kisiKisi?->school_id,
                'academic_year_id' => $data['academic_year_id'] ?? $book?->academic_year_id ?? $paket->kisiKisi?->academic_year_id,
                'semester' => $data['semester'] ?? $book?->semester ?? $paket->kisiKisi?->semester ?? 'ganjil',
                'admin_book_id' => $book?->id,
                'paket_soal_id' => $paket->id,
                'paket_version' => (int) $paket->versi,
                'paket_snapshot_at' => $paket->snapshot_at,
                'paket_digest' => $this->digestPaket($items),
                'study_group_id' => $data['study_group_id'] ?? $book?->study_group_id,
                'subject_id' => $book?->subject_id ?? $paket->kisiKisi?->subject_id,
                'template_id' => $template->id,
                'jenis_ujian' => $jenis,
                'judul' => $data['judul'],
                'tanggal' => $data['tanggal'] ?? null,
                'waktu_mulai' => $data['waktu_mulai'] ?? null,
                'waktu_selesai' => $data['waktu_selesai'] ?? null,
                'jumlah_soal' => $items->count(),
                'skor_config' => [
                    'total_weight' => round($totalWeight, 2),
                    'manual_count' => $manualCount,
                    'scale' => (float) config('ljk.grading.scale', 100.0),
                    // Mode fotokopi: semua peserta memakai urutan soal & opsi
                    // yang sama (kanonik) sehingga satu master dapat disalin
                    // untuk siapa pun.
                    'canonical_order' => (bool) ($data['canonical_order'] ?? false),
                ],
                'nilai_target' => $data['nilai_target'] ?? config("ljk.nilai_target.{$jenis}"),
                'status' => LjkExam::STATUS_DRAFT,
                'instruksi' => $data['instruksi'] ?? $paket->instruksi_umum,
                'created_by' => $actor->id,
            ]);

            return $exam;
        });
    }

    /**
     * Daftarkan peserta dari rombel (student_class_histories aktif).
     *
     * @return array<int, LjkParticipant>
     */
    public function provisionParticipants(LjkExam $exam, array $studentIds = []): array
    {
        $exam->loadMissing('paketSoal.items.soal.options');

        if ($studentIds === [] && $exam->study_group_id) {
            $studentIds = StudentClassHistory::query()
                ->where('study_group_id', $exam->study_group_id)
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('is_active', true)
                ->orderByRaw('COALESCE(attendance_number, 9999)')
                ->orderBy('student_id')
                ->pluck('student_id')
                ->unique()
                ->values()
                ->all();
        }

        if ($studentIds === []) {
            throw new RuntimeException('Tidak ada peserta yang dapat didaftarkan.');
        }

        $items = $exam->paketSoal->items()->with(['soal.options'])->orderBy('urutan')->get();

        $created = [];

        DB::transaction(function () use ($exam, $studentIds, $items, &$created) {
            $nextNomor = (int) LjkParticipant::query()->where('ljk_exam_id', $exam->id)->max('nomor_peserta');

            foreach ($studentIds as $studentId) {
                $studentId = (string) $studentId;

                $existing = LjkParticipant::query()
                    ->where('ljk_exam_id', $exam->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($existing) {
                    $created[] = $existing;
                    continue;
                }

                $nextNomor++;

                // Pemetaan aktual untuk peserta ini (urutan soal & opsi).
                $mapping = $this->buildMapping($exam, $items, $studentId);

                $created[] = LjkParticipant::create([
                    'ljk_exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'study_group_id' => $exam->study_group_id,
                    'nomor_peserta' => str_pad((string) $nextNomor, 4, '0', STR_PAD_LEFT),
                    'mapping' => $mapping,
                    'status' => LjkParticipant::STATUS_TERDAFTAR,
                ]);
            }
        });

        return $created;
    }

    /**
     * Bangun pemetaan nomor lembar → butir paket untuk satu peserta.
     *
     * Pemetaan deterministik per peserta (seed = exam+student) agar cetak
     * ulang menghasilkan lembar yang sama.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\PaketSoalItem>  $items
     * @return array{items: array<int, array<string, mixed>>}
     */
    public function buildMapping(LjkExam $exam, $items, string $studentId): array
    {
        $specs = $this->templates->specsFromItems($items);
        $paket = $exam->paketSoal;

        // Mode fotokopi: urutan seragam untuk semua peserta (tanpa pengacakan).
        $canonical = $this->usesCanonicalOrder($exam);

        $ordered = [];
        foreach ($specs as $spec) {
            $ordered[] = $spec;
        }

        if (! $canonical && $paket?->is_acak_urutan_soal) {
            mt_srand(crc32($exam->id.'|'.$studentId.'|order'));
            for ($i = count($ordered) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                [$ordered[$i], $ordered[$j]] = [$ordered[$j], $ordered[$i]];
            }
            mt_srand();
        }

        $mapping = [];
        foreach ($ordered as $index => $spec) {
            $labels = $spec['labels'] ?? [];

            if (! $canonical && $paket?->is_acak_opsi && count($labels) > 1) {
                mt_srand(crc32($exam->id.'|'.$studentId.'|opsi|'.$spec['item_id']));
                for ($i = count($labels) - 1; $i > 0; $i--) {
                    $j = mt_rand(0, $i);
                    [$labels[$i], $labels[$j]] = [$labels[$j], $labels[$i]];
                }
                mt_srand();
            }

            $mapping[] = [
                'no' => $index + 1,
                'item_id' => $spec['item_id'],
                'soal_id' => $spec['soal_id'],
                'jenis' => $spec['jenis'],
                'bobot' => $spec['bobot'],
                'opsi' => array_values($labels),
            ];
        }

        return ['items' => $mapping, 'generated_at' => now()->toIso8601String()];
    }

    /**
     * Apakah ujian memakai urutan kanonik (mode fotokopi / tanpa pengacakan).
     */
    public function usesCanonicalOrder(LjkExam $exam): bool
    {
        $config = $exam->skor_config ?? [];

        if ((bool) ($config['canonical_order'] ?? false)) {
            return true;
        }

        // Tanpa bendera eksplisit: pemetaan tetap kanonik bila paket tidak mengacak.
        $paket = $exam->paketSoal;

        return ! ($paket?->is_acak_urutan_soal) && ! ($paket?->is_acak_opsi);
    }

    /**
     * Terbitkan lembar (row + QR) untuk seluruh peserta.
     *
     * @return int jumlah lembar yang dibuat
     */
    public function generateSheets(LjkExam $exam, User $actor): int
    {
        $exam->loadMissing('template');

        $participants = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->with('sheet')
            ->orderBy('nomor_peserta')
            ->get();

        if ($participants->isEmpty()) {
            throw new RuntimeException('Daftarkan peserta terlebih dahulu.');
        }

        $count = 0;

        DB::transaction(function () use ($exam, $participants, &$count) {
            foreach ($participants as $participant) {
                if ($participant->sheet) {
                    continue;
                }

                $sheet = $this->sheets->createSheet($exam, $participant);
                $participant->forceFill([
                    'status' => LjkParticipant::STATUS_TERCETAK,
                    'sheet_generated_at' => now(),
                ])->save();
                $count++;
            }

            if ($exam->status === LjkExam::STATUS_DRAFT) {
                $exam->forceFill(['status' => LjkExam::STATUS_AKTIF])->save();
            }
        });

        return $count;
    }

    /**
     * Sidik jari naskah paket (urutan + identitas butir + kunci).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\PaketSoalItem>|iterable  $items
     */
    public function digestPaket(iterable $items): string
    {
        $canonical = [];
        foreach ($items as $item) {
            $konten = $item->kontenFinal();
            $opsi = array_map(static fn ($o) => [
                'label' => (string) ($o['label'] ?? ''),
                'kunci' => (bool) ($o['kunci'] ?? false),
            ], $konten['opsi'] ?? []);

            $canonical[] = [
                'nomor' => (int) ($konten['nomor'] ?? 0),
                'soal_id' => (string) ($konten['soal_id'] ?? ''),
                'jenis' => (string) ($konten['jenis'] ?? ''),
                'bobot' => (float) ($konten['bobot'] ?? 0),
                'opsi' => $opsi,
            ];
        }

        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Verifikasi bahwa paket masih identik dengan saat LJK diterbitkan.
     *
     * @return array{ok: bool, current: string, stored: ?string}
     */
    public function verifyPaketDigest(LjkExam $exam): array
    {
        $exam->loadMissing('paketSoal.items.soal.options');
        $current = $this->digestPaket($exam->paketSoal?->items ?? collect());

        return [
            'ok' => hash_equals((string) $exam->paket_digest, $current),
            'current' => $current,
            'stored' => $exam->paket_digest,
        ];
    }
}
