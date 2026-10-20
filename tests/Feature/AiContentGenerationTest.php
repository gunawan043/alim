<?php

namespace Tests\Feature;

use App\Http\Controllers\Kurikulum\AiContentController;
use App\Models\AcademicYear;
use App\Models\AiGeneratedContent;
use App\Models\AiProviderSetting;
use App\Models\BankSoal;
use App\Models\CurriculumProfile;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\PerangkatPembelajaran;
use App\Models\School;
use App\Models\SchoolCurriculumSetting;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AcademicPeriodService;
use App\Services\Ai\AiContentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * V10 — AI Assistant untuk draft RPM & soal (memakai provider dari Pengaturan AI).
 */
class AiContentGenerationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private GradeLevel $grade;

    private Subject $subject;

    private User $admin;

    private AiProviderSetting $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit AI', 'code' => 'UAI',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '10101010',
            'name' => 'Sekolah Uji AI',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'FIQ', 'name' => 'Fiqih',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin AI', 'email' => 'sa.ai.content@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->provider = AiProviderSetting::create([
            'name' => 'Provider Uji',
            'provider' => 'openai',
            'endpoint' => 'https://ai.test/v1/chat/completions',
            'api_key' => 'sk-test',
            'model' => 'test-model',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function request(array $data = []): Request
    {
        $request = Request::create('/kurikulum/ai/generate', 'POST', $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        return $request;
    }

    private function aiResponse(string $content): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $content]]],
            ]),
        ]);
    }

    private function basePayload(string $type): array
    {
        return [
            'type' => $type,
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'topik' => 'Thaharah dan shalat',
            'bahasa' => 'id',
        ];
    }

    public function test_generate_draft_soal_json_tersimpan(): void
    {
        $this->aiResponse(json_encode([
            'items' => [
                [
                    'pertanyaan' => 'Apa rukun Islam pertama?',
                    'bentuk' => 'pg',
                    'pilihan' => ['A' => 'Syahadat', 'B' => 'Shalat', 'C' => 'Zakat', 'D' => 'Haji'],
                    'jawaban' => 'A',
                    'pembahasan' => 'Rukun Islam pertama adalah syahadat.',
                    'level' => 'C1',
                    'indikator' => 'Menyebutkan rukun Islam',
                ],
                [
                    'pertanyaan' => 'Jelaskan tata cara wudhu.',
                    'bentuk' => 'essay',
                    'pilihan' => [],
                    'jawaban' => 'Niat, membasuh tangan, berkumur, ...',
                    'pembahasan' => 'Wudhu dilakukan berurutan.',
                    'level' => 'C3',
                ],
            ],
        ]));

        $request = $this->request(array_merge($this->basePayload('soal'), [
            'jumlah_soal' => 2,
            'bentuk_soal' => 'campuran',
            'level_kognitif' => 'campuran',
        ]));

        $response = app(AiContentController::class)->generate($request, $this->admin->id, app(AiContentGenerator::class));

        $this->assertTrue($response->isRedirect());

        $record = AiGeneratedContent::firstOrFail();
        $this->assertSame('soal', $record->type);
        $this->assertSame('draft', $record->status);
        $this->assertSame('Provider Uji', $record->provider);
        $this->assertSame('test-model', $record->model);
        $this->assertCount(2, $record->content['items']);
        $this->assertSame('Apa rukun Islam pertama?', $record->content['items'][0]['pertanyaan']);
        $this->assertNotNull($record->raw_content);
    }

    public function test_generate_draft_rpm_json_tersimpan(): void
    {
        $this->aiResponse(json_encode([
            'judul' => 'RPM Fiqih — Thaharah',
            'identitas' => ['mata_pelajaran' => 'Fiqih', 'kelas' => 'Kelas 7', 'semester' => 'Ganjil'],
            'capaian' => 'Memahami thaharah.',
            'tujuan' => ['Menjelaskan wudhu', 'Mempraktikkan wudhu'],
            'alur' => ['Wudhu', 'Shalat'],
            'langkah' => [
                ['pertemuan' => 1, 'pembuka' => 'Doa', 'inti' => 'Praktik wudhu', 'penutup' => 'Refleksi'],
            ],
            'asesmen' => ['formatif' => 'Observasi', 'sumatif' => 'Tes tulis'],
            'media' => ['Air', 'Video'],
            'refleksi' => 'Apa yang belum dipahami?',
        ]));

        $request = $this->request(array_merge($this->basePayload('rpm'), [
            'jumlah_pertemuan' => 1,
        ]));

        app(AiContentController::class)->generate($request, $this->admin->id, app(AiContentGenerator::class));

        $record = AiGeneratedContent::firstOrFail();
        $this->assertSame('rpm', $record->type);
        $this->assertSame('RPM Fiqih — Thaharah', $record->content['judul']);
        $this->assertCount(2, $record->content['tujuan']);
        $this->assertCount(1, $record->content['langkah']);
    }

    public function test_output_bukan_json_tetap_disimpan_sebagai_raw(): void
    {
        $this->aiResponse('Ini bukan JSON, tetapi tetap berguna sebagai draft.');

        $request = $this->request($this->basePayload('rpm'));
        app(AiContentController::class)->generate($request, $this->admin->id, app(AiContentGenerator::class));

        $record = AiGeneratedContent::firstOrFail();
        $this->assertNull($record->content);
        $this->assertStringContainsString('bukan JSON', $record->raw_content);
    }

    public function test_approve_draft_dan_audit(): void
    {
        $this->aiResponse(json_encode(['items' => [['pertanyaan' => 'Soal?', 'bentuk' => 'essay', 'jawaban' => 'Jawab']]]));
        app(AiContentController::class)->generate(
            $this->request($this->basePayload('soal')),
            $this->admin->id,
            app(AiContentGenerator::class)
        );

        $record = AiGeneratedContent::firstOrFail();

        $request = Request::create('/approve', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        app(AiContentController::class)->approve($request, $this->admin->id, $record->id);

        $record->refresh();
        $this->assertSame('approved', $record->status);
        $this->assertSame($this->admin->id, $record->approved_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'AI_CONTENT_APPROVED',
            'record_id' => $record->id,
        ]);
    }

    public function test_generate_ditolak_saat_provider_belum_dikonfigurasi(): void
    {
        $this->provider->delete();
        config()->set('arabic_names.ai.endpoint', '');

        $request = $this->request($this->basePayload('soal'));

        $response = app(AiContentController::class)->generate(
            $request,
            $this->admin->id,
            app(AiContentGenerator::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertSame(0, AiGeneratedContent::count());
        $this->assertSame('Provider AI belum dikonfigurasi. Hubungi Super Admin untuk mengisi API key di Pengaturan AI.', session('error'));
    }

    public function test_edit_butir_soal_pada_draft(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'bahasa' => 'id',
            'type' => 'soal',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft Soal',
            'content' => ['items' => [[
                'pertanyaan' => 'Pertanyaan lama',
                'bentuk' => 'pg',
                'pilihan' => ['A' => 'A', 'B' => 'B'],
                'jawaban' => 'A',
                'level' => 'C1',
            ]]],
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/items', 'POST', [
            'items' => [[
                'pertanyaan' => 'Pertanyaan baru?',
                'bentuk' => 'pg',
                'pilihan' => ['A' => 'Opsi A', 'B' => 'Opsi B', 'C' => '', 'D' => ''],
                'jawaban' => 'B',
                'pembahasan' => 'Pembahasan baru',
                'level' => 'C2',
                'indikator' => 'Indikator baru',
            ]],
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        app(AiContentController::class)->updateItems($request, $this->admin->id, $record->id);

        $item = $record->fresh()->content['items'][0];
        $this->assertSame('Pertanyaan baru?', $item['pertanyaan']);
        $this->assertSame('B', $item['jawaban']);
        $this->assertArrayNotHasKey('C', $item['pilihan']);
    }

    public function test_simpan_draft_soal_approved_ke_bank_soal(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'bahasa' => 'id',
            'type' => 'soal',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft Soal Approved',
            'topik' => 'Thaharah',
            'content' => ['items' => [
                [
                    'pertanyaan' => 'Apa rukun Islam pertama?',
                    'bentuk' => 'pg',
                    'pilihan' => ['A' => 'Syahadat', 'B' => 'Shalat', 'C' => 'Zakat', 'D' => 'Haji'],
                    'jawaban' => 'A',
                    'pembahasan' => 'Syahadat.',
                    'level' => 'C1',
                ],
                [
                    'pertanyaan' => 'Jelaskan wudhu.',
                    'bentuk' => 'essay',
                    'pilihan' => [],
                    'jawaban' => 'Niat lalu membasuh anggota wudhu.',
                    'level' => 'C3',
                ],
            ]],
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/save-bank', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiContentController::class)->saveToBank($request, $this->admin->id, $record->id);

        $this->assertTrue($response->isRedirect());

        $bank = BankSoal::firstOrFail();
        $this->assertSame(2, $bank->total_soal);
        $this->assertSame(2, Soal::where('bank_soal_id', $bank->id)->count());
        $this->assertSame(4, SoalOption::count());
        $this->assertSame($bank->id, $record->fresh()->saved_bank_id);
        $this->assertNotNull($record->fresh()->saved_at);

        // Simpan ulang ditolak.
        try {
            app(AiContentController::class)->saveToBank($request, $this->admin->id, $record->id);
            $this->fail('Simpan ulang seharusnya ditolak.');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
    }

    public function test_simpan_draft_soal_belum_approved_ditolak(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'soal',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft Belum Approved',
            'content' => ['items' => [['pertanyaan' => 'Soal?', 'bentuk' => 'essay', 'jawaban' => 'Jawab']]],
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/save-bank', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        try {
            app(AiContentController::class)->saveToBank($request, $this->admin->id, $record->id);
            $this->fail('Draft belum approved seharusnya ditolak.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(0, BankSoal::count());
    }

    public function test_simpan_draft_rpm_approved_ke_perangkat(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'rpm',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft RPM Approved',
            'topik' => 'Thaharah',
            'content' => [
                'judul' => 'RPM Fiqih — Thaharah',
                'capaian' => 'Memahami thaharah.',
                'tujuan' => ['Menjelaskan wudhu'],
                'langkah' => [
                    ['pertemuan' => 1, 'pembuka' => 'Doa', 'inti' => 'Praktik wudhu', 'penutup' => 'Refleksi'],
                ],
                'asesmen' => ['formatif' => 'Observasi', 'sumatif' => 'Tes tulis'],
                'media' => ['Air', 'Video'],
                'refleksi' => 'Apa yang belum dipahami?',
            ],
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/save-rpm', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiContentController::class)->saveToRpm($request, $this->admin->id, $record->id);

        $this->assertTrue($response->isRedirect());

        $perangkat = PerangkatPembelajaran::firstOrFail();
        $this->assertSame('RPM Fiqih — Thaharah', $perangkat->judul);
        $this->assertSame('draft', $perangkat->status);
        $this->assertSame('ai', $perangkat->source);
        $this->assertSame('Doa', $perangkat->desain['kegiatan_awal']);
        $this->assertSame('Observasi', $perangkat->desain['asesmen_formatif']);
        $this->assertSame($perangkat->id, $record->fresh()->saved_perangkat_id);
    }

    public function test_halaman_detail_draft_soal_dapat_dirender(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'soal',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft Soal UI',
            'content' => ['items' => [[
                'pertanyaan' => 'Soal untuk UI?',
                'bentuk' => 'pg',
                'pilihan' => ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'],
                'jawaban' => 'A',
            ]]],
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/kurikulum/ai/{$record->id}")
            ->assertOk()
            ->assertSee('Simpan Perubahan Butir');
    }

    public function test_simpan_rpm_ditolak_saat_periode_terkunci(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'rpm',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft RPM Terkunci',
            'content' => [
                'judul' => 'RPM Terkunci',
                'capaian' => 'Memahami thaharah.',
                'langkah' => [['pertemuan' => 1, 'pembuka' => 'Doa', 'inti' => 'Praktik', 'penutup' => 'Refleksi']],
            ],
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        app(AcademicPeriodService::class)->lock($this->academicYear->fresh(), $this->admin);

        $request = Request::create('/save-rpm', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        try {
            app(AiContentController::class)->saveToRpm($request, $this->admin->id, $record->id);
            $this->fail('Periode terkunci seharusnya menolak simpan RPM.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(0, PerangkatPembelajaran::count());
    }

    public function test_draft_tidak_dapat_dilihat_oleh_guru_lain(): void
    {
        $other = User::create([
            'name' => 'Guru Lain AI', 'email' => 'guru.lain.ai@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'soal',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft Privat',
            'content' => ['items' => [['pertanyaan' => 'Privat?', 'bentuk' => 'essay']]],
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/show', 'GET');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $other);

        try {
            app(AiContentController::class)->show($request, $other->id, $record->id);
            $this->fail('Draft milik guru lain seharusnya tidak dapat dilihat.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_simpan_ke_bank_menolak_pg_tanpa_opsi_yang_cukup(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'soal',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'PG Tanpa Opsi',
            'content' => ['items' => [[
                'pertanyaan' => 'Soal tanpa opsi?',
                'bentuk' => 'pg',
                'pilihan' => ['A' => 'Hanya satu'],
                'jawaban' => 'A',
            ]]],
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/save-bank', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        try {
            app(AiContentController::class)->saveToBank($request, $this->admin->id, $record->id);
            $this->fail('PG tanpa opsi yang cukup seharusnya ditolak.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertSame(0, BankSoal::count());
        $this->assertNull($record->fresh()->saved_bank_id);
    }

    public function test_draft_yang_sudah_disimpan_tidak_dapat_dihapus(): void
    {
        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'soal',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft Tersimpan',
            'content' => ['items' => [['pertanyaan' => 'Tersimpan?', 'bentuk' => 'essay']]],
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'saved_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/destroy', 'DELETE');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        try {
            app(AiContentController::class)->destroy($request, $this->admin->id, $record->id);
            $this->fail('Draft yang sudah disimpan seharusnya tidak dapat dihapus.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertDatabaseHas('ai_generated_contents', ['id' => $record->id]);
    }

    public function test_guru_hanya_melihat_dan_membuat_draft_mapel_yang_diampu(): void
    {
        $otherSubject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'BIO', 'name' => 'Biologi',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        $teacher = User::create([
            'name' => 'Guru AI Mapel', 'email' => 'guru.ai.mapel@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $decree = InstitutionDecree::create([
            'decree_number' => 'SK-AI-001',
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'SK Uji AI',
            'academic_year_id' => $this->academicYear->id,
            'school_id' => $this->school->id,
            'issued_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        TeachingAssignment::create([
            'decree_id' => $decree->id,
            'teacher_id' => $teacher->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id' => $group->id,
            'subject_id' => $this->subject->id,
            'status' => 'active',
        ]);

        $request = Request::create('/ai', 'GET');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $teacher);

        $view = app(AiContentController::class)->index($request, $teacher->id);
        $subjects = $view->getData()['subjects'];

        $this->assertTrue($subjects->contains('id', $this->subject->id));
        $this->assertFalse($subjects->contains('id', $otherSubject->id));

        $request = Request::create('/ai/generate', 'POST', [
            'type' => 'soal',
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'topik' => 'Sel',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $teacher);

        try {
            app(AiContentController::class)->generate($request, $teacher->id, app(AiContentGenerator::class));
            $this->fail('Guru tidak boleh membuat draft untuk mapel yang tidak diampu.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_halaman_ai_dapat_dirender(): void
    {
        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/kurikulum/ai")
            ->assertOk()
            ->assertSee('AI Assistant');
    }

    public function test_simpan_draft_rpm_kbc_memetakan_nilai_cinta_ke_perangkat(): void
    {
        $this->seed(\Database\Seeders\CurriculumProfileSeeder::class);
        $profile = CurriculumProfile::where('code', 'kbc_kemenag')->firstOrFail();

        SchoolCurriculumSetting::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'curriculum_profile_id' => $profile->id,
            'kokurikuler_type' => 'p5ra',
        ]);

        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'curriculum_profile_id' => $profile->id,
            'semester' => 'ganjil',
            'type' => 'rpm',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft Modul Ajar KBC',
            'topik' => 'Thaharah',
            'content' => [
                'judul' => 'Modul Ajar Fiqih — Thaharah',
                'capaian' => 'Memahami thaharah.',
                'langkah' => [['pertemuan' => 1, 'pembuka' => 'Doa', 'inti' => 'Praktik wudhu', 'penutup' => 'Refleksi']],
                'asesmen' => ['formatif' => 'Observasi', 'sumatif' => 'Tes tulis'],
                'kbc' => [
                    'tema' => 'Cinta kepada Allah',
                    'materi_insersi' => 'Menjaga kesucian sebagai bentuk cinta kepada Allah.',
                    'pilar' => ['Cinta kepada Allah'],
                    'indikator' => 'Peserta didik menjaga wudhu dengan sungguh-sungguh.',
                    'refleksi' => 'Bagaimana menjaga kesucian?',
                    'asesmen' => 'Observasi sikap menjaga kesucian.',
                ],
            ],
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/save-rpm', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        app(AiContentController::class)->saveToRpm($request, $this->admin->id, $record->id);

        $perangkat = PerangkatPembelajaran::firstOrFail();
        $this->assertSame($profile->id, $perangkat->curriculum_profile_id);
        $this->assertSame('Cinta kepada Allah', $perangkat->desain['tema_kbc']);
        $this->assertSame('Peserta didik menjaga wudhu dengan sungguh-sungguh.', $perangkat->desain['indikator_kbc']);
        $this->assertSame('Cinta kepada Allah', $perangkat->desain['pilar_kbc']);
    }

    public function test_ai_tidak_menimpa_perangkat_manual_yang_sudah_ada(): void
    {
        $manual = PerangkatPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'teacher_id' => $this->admin->id,
            'judul' => 'Perangkat Manual Fiqih',
            'tipe' => PerangkatPembelajaran::TIPE_UMUM,
            'source' => 'manual',
            'status' => 'draft',
            'is_current' => true,
            'desain' => PerangkatPembelajaran::defaultDesain(),
            'created_by' => $this->admin->id,
        ]);

        $record = AiGeneratedContent::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'rpm',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'title' => 'Draft AI yang Tidak Boleh Menimpa',
            'content' => [
                'judul' => 'Draft AI',
                'langkah' => [['pertemuan' => 1, 'pembuka' => 'Doa', 'inti' => 'Materi', 'penutup' => 'Refleksi']],
            ],
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $request = Request::create('/save-rpm', 'POST');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->admin);

        $response = app(AiContentController::class)->saveToRpm($request, $this->admin->id, $record->id);

        $this->assertTrue($response->isRedirect());
        $this->assertSame(1, PerangkatPembelajaran::count());
        $this->assertSame('Perangkat Manual Fiqih', $manual->fresh()->judul);
        $this->assertNull($record->fresh()->saved_perangkat_id);
    }
}
