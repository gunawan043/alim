<?php

namespace Tests\Feature\Ljk;

use App\Models\BankSoal;
use App\Models\KisiKisiSoal;
use App\Models\LjkAnswerCorrection;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScanAnswer;
use App\Models\NilaiSumatif;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\Evaluasi\PaketNaskahService;
use App\Services\Ljk\LjkExamService;
use App\Services\Ljk\LjkReviewService;
use App\Services\Ljk\LjkScanService;
use App\Services\Ljk\LjkSyntheticRenderer;
use App\Services\Ljk\LjkSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Alur lengkap: Paket Soal → LJK → pindai → nilai → koreksi guru → sinkron nilai.
 */
class LjkFlowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private StudyGroup $studyGroup;

    private TeacherAdminBook $book;

    private PaketSoal $paket;

    private Collection $students;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnit = \App\Models\WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Yayasan Alim',
            'code' => 'YYS-ALIM',
            'is_active' => true,
        ]);

        $this->school = School::create([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $workUnit->id,
            'name' => 'MA Alim',
            'npsn' => '12345678',
            'school_level' => 'sma',
        ]);

        $this->teacher = $this->makeUser('Ustadz Guru', $this->school->id);

        $academicYear = \App\Models\AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $subject = \App\Models\Subject::create([
            'id' => (string) Str::uuid(),
            'name' => 'Fiqih',
            'code' => 'FQH',
            'school_id' => $this->school->id,
        ]);

        $gradeLevel = \App\Models\GradeLevel::create([
            'id' => (string) Str::uuid(),
            'name' => 'X',
            'level' => 10,
            'school_id' => $this->school->id,
        ]);

        $this->studyGroup = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'X-A',
            'is_active' => true,
        ]);

        $this->book = TeacherAdminBook::create([
            'id' => (string) Str::uuid(),
            'teacher_id' => $this->teacher->id,
            'subject_id' => $subject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'is_active' => true,
            'nr_final_weight_rs' => 50,
            'nr_final_weight_sts' => 25,
            'nr_final_weight_sas' => 25,
        ]);

        $this->students = collect();
        foreach (['Santri A', 'Santri B', 'Santri C'] as $i => $name) {
            $student = Student::create([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'nis' => '100'.$i,
                'nisn' => '90000'.$i,
                'gender' => 'L',
                'school_id' => $this->school->id,
                'status' => 'active',
            ]);

            StudentClassHistory::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
                'study_group_id' => $this->studyGroup->id,
                'academic_year_id' => $academicYear->id,
                'attendance_number' => $i + 1,
                'is_active' => true,
            ]);

            $this->students->push($student);
        }

        $this->paket = $this->makePaket($subject->id, $academicYear->id, $gradeLevel->id, $this->school->id, $this->teacher->id);
    }

    private function makeUser(string $name, string $schoolId): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'email' => strtolower(Str::random(8)).'@alim.test',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);
    }

    private function makePaket(string $subjectId, string $yearId, string $gradeLevelId, string $schoolId, string $userId): PaketSoal
    {
        $bank = BankSoal::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'grade_level_id' => $gradeLevelId,
            'academic_year_id' => $yearId,
            'semester' => 'ganjil',
            'nama' => 'Bank Fiqih',
            'jenis_soal' => 'pilihan_ganda',
            'owner_user_id' => $userId,
            'created_by' => $userId,
        ]);

        $kisi = KisiKisiSoal::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'grade_level_id' => $gradeLevelId,
            'academic_year_id' => $yearId,
            'created_by' => $userId,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi Fiqih',
            'tingkat_sekolah' => 'sma',
            'is_active' => true,
        ]);

        $paket = PaketSoal::create([
            'id' => (string) Str::uuid(),
            'kisi_kisi_soal_id' => $kisi->id,
            'judul' => 'STS Fiqih Ganjil',
            'kode_paket' => 'PKT-TEST-'.Str::random(4),
            'versi' => 1,
            'workflow_status' => PaketSoal::WORKFLOW_PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
            'jumlah_soal_aktual' => 0,
            'total_bobot_aktual' => 0,
        ]);

        $keys = [];

        for ($i = 1; $i <= 10; $i++) {
            $soal = Soal::create([
                'id' => (string) Str::uuid(),
                'bank_soal_id' => $bank->id,
                'tipe_soal' => 'pg',
                'pertanyaan' => "Pertanyaan {$i}",
                'bobot_default' => 1,
                'status' => 'approved',
                'workflow_status' => Soal::WORKFLOW_APPROVED,
                'content_hash' => hash('sha256', 'soal-'.$i.'-'.Str::random(6)),
                'dibuat_oleh' => $userId,
            ]);

            $correct = ['A', 'B', 'C', 'D'][$i % 4];
            $keys[$i] = $correct;

            foreach (['A', 'B', 'C', 'D'] as $idx => $label) {
                SoalOption::create([
                    'id' => (string) Str::uuid(),
                    'soal_id' => $soal->id,
                    'label' => $label,
                    'teks_opsi' => "Opsi {$label}",
                    'is_correct' => $label === $correct,
                    'urutan' => $idx + 1,
                ]);
            }

            PaketSoalItem::create([
                'id' => (string) Str::uuid(),
                'paket_soal_id' => $paket->id,
                'soal_id' => $soal->id,
                'urutan' => $i,
                'nomor' => $i,
                'jenis_soal' => 'pg',
            ]);
        }

        $paket->recomputeTotals();
        app(PaketNaskahService::class)->snapshot($paket);

        return $paket->fresh();
    }

    private function createExam(int $jenisUjian = 0): LjkExam
    {
        return app(LjkExamService::class)->createExam([
            'judul' => 'STS Fiqih',
            'paket_soal_id' => $this->paket->id,
            'admin_book_id' => $this->book->id,
            'study_group_id' => $this->studyGroup->id,
            'jenis_ujian' => 'sts',
            'semester' => 'ganjil',
        ], $this->teacher);
    }

    /**
     * Render lembar sintetis untuk peserta dengan jawaban tertentu.
     *
     * @param  array<int, string>  $slotAnswers  nomor => slot (A/B/C/D) — '' kosong, 'AB' ganda
     */
    private function renderSheet(LjkExam $exam, LjkParticipant $participant, array $slotAnswers, array $styles = []): string
    {
        $geometry = $exam->template->geometry;

        $path = app(LjkSyntheticRenderer::class)->renderToFile($geometry, [
            'answers' => $slotAnswers,
            'styles' => $styles,
        ], [
            'participant_seq' => (int) $participant->nomor_peserta,
            'path' => sys_get_temp_dir().'/ljk-flow-'.Str::random(8).'.jpg',
        ]);
        return $path;
    }

    private function upload(string $path, LjkExam $exam, ?LjkParticipant $participant = null): array
    {
        $file = new UploadedFile($path, basename($path), 'image/jpeg', null, true);

        return app(LjkScanService::class)->storeAndProcess([
            'exam' => $exam,
            'file' => $file,
            'participant' => $participant,
            'source' => 'test',
        ], $this->teacher);
    }

    /**
     * Slot yang berisi kunci untuk tiap nomor pada pemetaan peserta.
     *
     * @return array<int, string>
     */
    private function correctSlots(LjkExam $exam, LjkParticipant $participant): array
    {
        $keys = [];
        foreach ($participant->mappingItems() as $item) {
            $keyLabel = $this->keyFor($item['item_id']);
            $slotIndex = array_search($keyLabel, $item['opsi'] ?? [], true);
            $keys[(int) $item['no']] = $slotIndex === false ? 'A' : chr(65 + (int) $slotIndex);
        }

        return $keys;
    }

    private function keyFor(string $itemId): string
    {
        $item = PaketSoalItem::find($itemId);
        foreach ($item->kontenFinal()['opsi'] ?? [] as $option) {
            if ($option['kunci'] ?? false) {
                return (string) $option['label'];
            }
        }

        return 'A';
    }

    public function test_alur_lengkap_paket_soal_ke_ljk_ke_nilai_resmi(): void
    {
        $exam = $this->createExam();

        $this->assertSame($this->paket->id, $exam->paket_soal_id);
        $this->assertNotNull($exam->paket_digest);
        $this->assertNotNull($exam->paket_snapshot_at);

        // Peserta & lembar
        $participants = app(LjkExamService::class)->provisionParticipants($exam);
        $this->assertCount(3, $participants);

        $created = app(LjkExamService::class)->generateSheets($exam, $this->teacher);
        $this->assertSame(3, $created);
        $this->assertSame(LjkExam::STATUS_AKTIF, $exam->fresh()->status);

        $participant = $participants[0];
        $sheet = $participant->fresh()->sheet;
        $this->assertNotNull($sheet);
        $this->assertStringStartsWith($exam->code, $sheet->sheet_code);

        // QR payload tertandatangani & tanpa kunci jawaban
        $payload = app(\App\Services\Ljk\LjkSheetService::class);
        $verified = $payload->verifyQrPayload($sheet->qr_token);
        $this->assertNotNull($verified);
        $this->assertSame($sheet->sheet_code, $verified['s']);
        $this->assertStringNotContainsString('kunci', $sheet->qr_token);

        // Jawaban: 8 benar, 1 salah, 1 kosong
        $slots = $this->correctSlots($exam, $participant);
        $answers = $slots;
        $answers[3] = $slots[3] === 'A' ? 'B' : 'A'; // salah
        $answers[7] = ''; // kosong

        $result = $this->upload($this->renderSheet($exam, $participant, $answers), $exam, $participant);
        $scan = $result['scan'];

        $this->assertFalse($result['duplicate']);
        $this->assertSame(\App\Models\LjkScan::STATUS_PROCESSED, $scan->status, json_encode($scan->quality));
        $this->assertSame(10, $scan->answers->count());

        $byNumber = $scan->answers->keyBy('nomor');
        $this->assertSame('terbaca', $byNumber[1]->status);
        $this->assertTrue((bool) $byNumber[1]->is_correct);
        $this->assertSame('kosong', $byNumber[7]->status);
        $this->assertSame('terbaca', $byNumber[3]->status);
        $this->assertFalse((bool) $byNumber[3]->is_correct);

        $participant->refresh();
        $this->assertNotNull($participant->nilai_akhir);
        $this->assertEqualsWithDelta(80.0, (float) $participant->nilai_akhir, 0.01);

        // Duplikasi pemindaian ditolak (dikembalikan sebagai referensi)
        $again = $this->upload($this->renderSheet($exam, $participant, $answers), $exam, $participant);
        $this->assertTrue($again['duplicate']);
        $this->assertSame($scan->id, $again['scan']->id);
    }

    public function test_koreksi_guru_tercatat_dan_mengubah_nilai(): void
    {
        $exam = $this->createExam();
        $participants = app(LjkExamService::class)->provisionParticipants($exam);
        app(LjkExamService::class)->generateSheets($exam, $this->teacher);

        $participant = $participants[0];
        $slots = $this->correctSlots($exam, $participant);
        $answers = $slots;
        $answers[1] = $slots[1] === 'A' ? 'B' : 'A'; // salah

        $scan = $this->upload($this->renderSheet($exam, $participant, $answers), $exam, $participant)['scan'];
        $wrong = $scan->answers->firstWhere('nomor', 1);

        $this->assertFalse((bool) $wrong->is_correct);

        // Guru mengoreksi: jawaban sebenarnya adalah kunci.
        $keyLabel = $this->keyFor(collect($participant->mappingItems())->firstWhere('no', 1)['item_id']);
        $corrected = app(LjkReviewService::class)->correct(
            $wrong,
            ['status' => 'terbaca', 'label' => $keyLabel],
            $this->teacher,
            'Santri menunjukkan coretan ulang yang sah.'
        );

        $this->assertTrue((bool) $corrected->fresh()->is_correct);
        $this->assertSame('corrected', $corrected->fresh()->review_action);
        $this->assertSame($this->teacher->id, $corrected->fresh()->reviewed_by);

        // Log koreksi append-only dengan nilai sebelum/sesudah.
        $correction = LjkAnswerCorrection::where('ljk_scan_answer_id', $wrong->id)->first();
        $this->assertNotNull($correction);
        $this->assertSame('terbaca', $correction->after['status']);
        $this->assertSame($keyLabel, $correction->after['selected']);
        $this->assertSame($this->teacher->id, $correction->corrected_by);
        $this->assertNotNull($correction->corrected_at);

        // Nilai peserta naik menjadi 100.
        $this->assertEqualsWithDelta(100.0, (float) $participant->fresh()->nilai_akhir, 0.01);
    }

    public function test_sinkron_nilai_masuk_ke_modul_nilai_resmi_dan_idempotent(): void
    {
        $exam = $this->createExam();
        $participants = app(LjkExamService::class)->provisionParticipants($exam);
        app(LjkExamService::class)->generateSheets($exam, $this->teacher);

        // Pindai semua peserta dengan jawaban benar semua.
        foreach ($participants as $participant) {
            $slots = $this->correctSlots($exam, $participant);
            $this->upload($this->renderSheet($exam, $participant, $slots), $exam, $participant);
        }

        $summary = app(\App\Services\Ljk\LjkGradingService::class)->reviewSummary($exam->fresh());
        $this->assertSame(3, $summary['scanned']);
        $this->assertSame(0, $summary['needs_review']);

        // Nilai sudah masuk OTOMATIS setelah pemindaian bersih (tanpa tombol sinkron).
        $auto = NilaiSumatif::where('admin_book_id', $this->book->id)->get();
        $this->assertCount(3, $auto, 'Nilai harus otomatis tersimpan saat hasil bersih.');
        $this->assertEqualsWithDelta(100.0, (float) $auto->first()->sts_murni, 0.01);
        $this->assertDatabaseHas('ljk_sync_logs', ['ljk_exam_id' => $exam->id, 'action' => 'auto']);

        $result = app(LjkSyncService::class)->sync($exam->fresh(), $this->teacher);
        $this->assertFalse($result['skipped']);
        $this->assertSame(3, $result['count']);

        // Nilai masuk ke kolom resmi (sts_murni) + provenans LJK.
        $row = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $this->students[0]->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(100.0, (float) $row->sts_murni, 0.01);
        $this->assertSame($this->paket->id, $row->paket_soal_id);
        $this->assertSame($exam->id, $row->ljk_exam_id);
        $this->assertSame(\App\Models\LjkExam::STATUS_TERSINKRON, $exam->fresh()->status);

        // Sinkronisasi ulang dengan payload sama tidak menggandakan.
        $again = app(LjkSyncService::class)->sync($exam->fresh(), $this->teacher);
        $this->assertTrue($again['skipped']);
    }

    public function test_ganti_jawaban_ganda_dikoreksi_guru_dan_nilai_memakai_jawaban_baru(): void
    {
        $exam = $this->createExam();
        $participants = app(LjkExamService::class)->provisionParticipants($exam);
        app(LjkExamService::class)->generateSheets($exam, $this->teacher);

        $participant = $participants[0];
        $slots = $this->correctSlots($exam, $participant);
        $mapItem2 = collect($participant->mappingItems())->firstWhere('no', 2);
        $correct2 = $this->keyFor((string) $mapItem2['item_id']);

        // Siswa mengganti jawaban: tanda lama (A) masih terlihat + tanda baru (B).
        $slots[2] = 'AB';

        $scan = $this->upload($this->renderSheet($exam, $participant, $slots), $exam, $participant)['scan'];
        $answer = $scan->answers->firstWhere('nomor', 2);

        $this->assertSame('ganda', $answer->status);
        $this->assertNull($answer->selected_label, 'Jawaban ganda tidak boleh dipilih otomatis.');
        $this->assertTrue((bool) $answer->needs_review);
        $detected = (array) ($answer->evidence['selected_set'] ?? []);
        $this->assertEqualsCanonicalizing(['A', 'B'], $detected);

        // Selama menunggu koreksi, nilai belum masuk.
        $this->assertDatabaseMissing('admin_nilai_sumatif', [
            'admin_book_id' => $this->book->id,
            'student_id' => $participant->student_id,
        ]);

        // Guru memilih jawaban baru; koreksi tercatat sebelum/sesudah + alasan.
        $corrected = app(LjkReviewService::class)->correct(
            $answer,
            ['status' => 'terbaca', 'label' => $correct2],
            $this->teacher,
            'Siswa mengganti jawaban'
        );

        $this->assertSame('terbaca', $corrected->status);
        $this->assertSame($correct2, $corrected->selected_label);
        $this->assertFalse((bool) $corrected->needs_review);
        $this->assertDatabaseHas('ljk_answer_corrections', [
            'ljk_scan_answer_id' => $answer->id,
            'corrected_by' => $this->teacher->id,
            'reason' => 'Siswa mengganti jawaban',
        ]);

        // Koreksi bersih → nilai otomatis memakai kunci paket (semua benar = 100).
        $row = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $participant->student_id)
            ->first();

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(100.0, (float) $row->sts_murni, 0.01);
    }

    public function test_sinkron_ditolak_saat_masih_ada_yang_menunggu_pemeriksaan(): void
    {
        $exam = $this->createExam();
        $participants = app(LjkExamService::class)->provisionParticipants($exam);
        app(LjkExamService::class)->generateSheets($exam, $this->teacher);

        $participant = $participants[0];
        $slots = $this->correctSlots($exam, $participant);
        $slots[2] = 'AB'; // ganda → butuh pemeriksaan

        $scan = $this->upload($this->renderSheet($exam, $participant, $slots), $exam, $participant)['scan'];
        $this->assertTrue((bool) $scan->answers->firstWhere('nomor', 2)->needs_review);

        // Auto-sync TIDAK boleh menulis nilai selama masih ada nomor yang perlu diperiksa.
        $this->assertDatabaseMissing('admin_nilai_sumatif', [
            'admin_book_id' => $this->book->id,
            'student_id' => $participant->student_id,
        ]);

        // Peserta lain belum dipindai.
        $this->expectException(\RuntimeException::class);
        app(LjkSyncService::class)->sync($exam->fresh(), $this->teacher);
    }
}
