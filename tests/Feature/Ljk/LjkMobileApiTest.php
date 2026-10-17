<?php

namespace Tests\Feature\Ljk;

use App\Models\BankSoal;
use App\Models\KisiKisiSoal;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
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
use App\Services\Ljk\LjkSyntheticRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API mobile LJK: daftar ujian, pemindaian, koreksi — dengan otorisasi server.
 */
class LjkMobileApiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $otherTeacher;

    private StudyGroup $studyGroup;

    private TeacherAdminBook $book;

    private PaketSoal $paket;

    private LjkExam $exam;

    private LjkParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnit = \App\Models\WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Yayasan Alim',
            'code' => 'YYS-'.Str::random(6),
            'is_active' => true,
        ]);

        $this->school = School::create([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $workUnit->id,
            'name' => 'MA Alim',
            'npsn' => 'NPSN'.Str::random(6),
            'school_level' => 'sma',
        ]);

        $this->teacher = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Guru API',
            'email' => 'guru-api-'.Str::random(4).'@alim.test',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);

        $this->otherTeacher = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Guru Lain API',
            'email' => 'guru-lain-'.Str::random(4).'@alim.test',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);

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
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'name' => 'Santri API',
            'nis' => '3001',
            'nisn' => '9003001',
            'gender' => 'L',
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $academicYear->id,
            'attendance_number' => 1,
            'is_active' => true,
        ]);

        // Paket final sederhana (5 soal).
        $bank = BankSoal::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'nama' => 'Bank API',
            'jenis_soal' => 'pilihan_ganda',
            'owner_user_id' => $this->teacher->id,
            'created_by' => $this->teacher->id,
        ]);

        $kisi = KisiKisiSoal::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $academicYear->id,
            'created_by' => $this->teacher->id,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi API',
            'tingkat_sekolah' => 'sma',
            'is_active' => true,
        ]);

        $this->paket = PaketSoal::create([
            'id' => (string) Str::uuid(),
            'kisi_kisi_soal_id' => $kisi->id,
            'judul' => 'STS API',
            'kode_paket' => 'PKT-API-'.Str::random(5),
            'versi' => 1,
            'workflow_status' => PaketSoal::WORKFLOW_PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $soal = Soal::create([
                'id' => (string) Str::uuid(),
                'bank_soal_id' => $bank->id,
                'tipe_soal' => 'pg',
                'pertanyaan' => "Soal {$i}",
                'bobot_default' => 1,
                'status' => 'approved',
                'workflow_status' => Soal::WORKFLOW_APPROVED,
                'content_hash' => hash('sha256', 'api-soal-'.Str::random(8)),
                'dibuat_oleh' => $this->teacher->id,
            ]);

            foreach (['A', 'B', 'C', 'D'] as $idx => $label) {
                SoalOption::create([
                    'id' => (string) Str::uuid(),
                    'soal_id' => $soal->id,
                    'label' => $label,
                    'teks_opsi' => "Opsi {$label}",
                    'is_correct' => $label === 'A',
                    'urutan' => $idx + 1,
                ]);
            }

            PaketSoalItem::create([
                'id' => (string) Str::uuid(),
                'paket_soal_id' => $this->paket->id,
                'soal_id' => $soal->id,
                'urutan' => $i,
                'nomor' => $i,
                'jenis_soal' => 'pg',
            ]);
        }

        $this->paket->recomputeTotals();
        app(PaketNaskahService::class)->snapshot($this->paket);
        $this->paket = $this->paket->fresh();

        $this->exam = app(LjkExamService::class)->createExam([
            'judul' => 'STS API',
            'paket_soal_id' => $this->paket->id,
            'admin_book_id' => $this->book->id,
            'study_group_id' => $this->studyGroup->id,
            'jenis_ujian' => 'sts',
        ], $this->teacher);

        $participants = app(LjkExamService::class)->provisionParticipants($this->exam);
        app(LjkExamService::class)->generateSheets($this->exam, $this->teacher);
        $this->participant = $participants[0];
    }

    private function correctSlots(): array
    {
        $grading = app(\App\Services\Ljk\LjkGradingService::class);
        $slots = [];

        foreach ($this->participant->mappingItems() as $item) {
            $key = $grading->keyLabels($item['item_id'])[0] ?? 'A';
            $index = array_search($key, $item['opsi'] ?? [], true);
            $slots[(int) $item['no']] = $index === false ? 'A' : chr(65 + (int) $index);
        }

        return $slots;
    }

    public function test_daftar_ujian_dan_detail_peserta_api(): void
    {
        Sanctum::actingAs($this->teacher, [], 'sanctum');

        $this->getJson('/api/mobile/v1/ljk/exams')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exams.0.id', $this->exam->id);

        $this->getJson('/api/mobile/v1/ljk/exams/'.$this->exam->id)
            ->assertOk()
            ->assertJsonPath('data.participants.0.nomor_peserta', $this->participant->nomor_peserta);
    }

    public function test_guru_lain_tidak_melihat_ujian_di_api(): void
    {
        Sanctum::actingAs($this->otherTeacher, [], 'sanctum');

        $response = $this->getJson('/api/mobile/v1/ljk/exams')->assertOk();
        $this->assertSame([], $response->json('data.exams'));

        $this->getJson('/api/mobile/v1/ljk/exams/'.$this->exam->id)
            ->assertForbidden();
    }

    public function test_unggah_pemindaian_lewat_api_dan_koreksi(): void
    {
        Sanctum::actingAs($this->teacher, [], 'sanctum');

        $geometry = $this->exam->template->geometry;
        $path = app(LjkSyntheticRenderer::class)->renderToFile($geometry, [
            'answers' => $this->correctSlots(),
        ], ['participant_seq' => (int) $this->participant->nomor_peserta]);

        $response = $this->postJson('/api/mobile/v1/ljk/exams/'.$this->exam->id.'/scans', [
            'image' => new UploadedFile($path, 'ljk.jpg', 'image/jpeg', null, true),
            'participant_id' => $this->participant->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $scanId = $response->json('data.scan.id');
        $this->assertNotNull($scanId);
        $this->assertSame(5, count($response->json('data.scan.answers')));

        $answerId = $response->json('data.scan.answers.0.id');

        $this->patchJson("/api/mobile/v1/ljk/scans/{$scanId}/answers/{$answerId}", [
            'status' => 'kosong',
            'label' => null,
            'reason' => 'Kotak tidak diisi.',
        ])->assertOk()->assertJsonPath('data.answer.status', 'kosong');

        $this->assertDatabaseHas('ljk_answer_corrections', [
            'ljk_scan_answer_id' => $answerId,
            'corrected_by' => $this->teacher->id,
        ]);
    }

    public function test_pemindaian_tanpa_identitas_ditolak(): void
    {
        Sanctum::actingAs($this->teacher, [], 'sanctum');

        $geometry = $this->exam->template->geometry;
        $path = app(LjkSyntheticRenderer::class)->renderToFile($geometry, [
            'answers' => $this->correctSlots(),
        ], []);

        $this->postJson('/api/mobile/v1/ljk/exams/'.$this->exam->id.'/scans', [
            'image' => new UploadedFile($path, 'ljk.jpg', 'image/jpeg', null, true),
        ])->assertStatus(422)->assertJsonPath('error.code', 'SCAN_REJECTED');
    }
}
