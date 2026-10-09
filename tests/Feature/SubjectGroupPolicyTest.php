<?php

namespace Tests\Feature;

use App\Authorization\Providers\SubjectGroupPermissionProvider;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\GtkAdditionalTask;
use App\Models\KisiKisiSoal;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\ReviewAssignment;
use App\Models\Role;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Evaluasi\ContentHashEngine;
use App\Services\Evaluasi\ReviewWorkflowService;
use App\Services\SubjectGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tahap 1 — Fondasi Kebijakan: Rumpun Mata Pelajaran.
 *
 * Mencakup: master rumpun, pemetaan mapel→rumpun, keanggotaan guru dari
 * assignment, koordinator dari tugas tambahan, reviewer koordinator utama,
 * fallback rumpun, guard publish paket, dan masking kunci per sisi.
 */
class SubjectGroupPolicyTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group7a;

    private StudyGroup $group7b;

    private StudyGroup $group8a;

    private User $guruMath;

    private User $guruMath2;

    private User $guruFisika;

    private User $guruFiqih;

    private User $guruSki;

    private User $koordinatorUmum;

    private User $tu;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $this->seedFixture();
    }

    // ─────────────────────────────────────────────────────────────
    // MASTER RUMPUN & PEMETAAN
    // ─────────────────────────────────────────────────────────────

    public function test_master_rumpun_tersedia_dan_resolver_memetakan_mapel(): void
    {
        $this->assertSame(5, SubjectGroup::count());
        $this->assertSame(
            ['agama', 'bahasa_arab', 'hadits', 'tahfidz', 'umum'],
            SubjectGroup::orderBy('sort_order')->pluck('code')->sort()->values()->all()
        );

        $map = [
            'Matematika' => 'umum',
            'Fisika' => 'umum',
            'Fiqih' => 'agama',
            'SKI' => 'agama',
            'Hadits Arbain' => 'hadits',
            'Tahfidz Al-Quran' => 'tahfidz',
            'Bahasa Arab' => 'bahasa_arab',
        ];

        foreach ($map as $name => $expectedCode) {
            $subject = Subject::create([
                'school_id' => $this->school->id,
                'code' => strtoupper(substr($name, 0, 3)).rand(10, 99),
                'name' => $name,
                'credit_hours' => 2,
                'is_active' => true,
            ]);

            $this->assertNotNull($subject->subject_group_id, "Mapel {$name} harus otomatis dipetakan.");
            $this->assertSame(
                $expectedCode,
                $subject->subjectGroup->code,
                "Mapel {$name} harus masuk rumpun {$expectedCode}."
            );
        }

        // Prioritas: Tahfidz tidak boleh jatuh ke Agama yang lebih umum.
        $tahfidz = Subject::where('name', 'Tahfidz Al-Quran')->firstOrFail();
        $this->assertSame('tahfidz', $tahfidz->subjectGroup->code);
    }

    public function test_command_sync_memetakan_ulang_mapel_tanpa_rumpun(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'BIO01',
            'name' => 'Biologi',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        // Simulasikan data lama tanpa rumpun.
        DB::table('subjects')->where('id', $subject->id)->update(['subject_group_id' => null]);

        $this->artisan('alim:sync-subject-groups')->assertExitCode(0);

        $this->assertSame('umum', $subject->fresh()->subjectGroup->code);
    }

    // ─────────────────────────────────────────────────────────────
    // KEANGGOTAAN GURU & KOORDINATOR
    // ─────────────────────────────────────────────────────────────

    public function test_keanggotaan_guru_dari_penugasan_multi_rumpun(): void
    {
        $resolver = app(SubjectGroupResolver::class);

        // Guru Matematika (Umum) + Hadits → dua rumpun sesuai penugasan,
        // tidak mendapat akses otomatis ke rumpun lain (mis. Tahfidz).
        $codes = $resolver->groupsForTeacher($this->guruMath)->pluck('code')->sort()->values()->all();
        $this->assertSame(['hadits', 'umum'], $codes);

        // Guru Fisika → hanya Umum.
        $this->assertSame(['umum'], $resolver->groupsForTeacher($this->guruFisika)->pluck('code')->all());

        // Provider permission menghasilkan permission rumpun yang benar.
        $permissions = collect(app(SubjectGroupPermissionProvider::class)->provide($this->guruMath->id))
            ->pluck('permission')
            ->all();

        $this->assertContains('subject-group.umum.member', $permissions);
        $this->assertContains('subject-group.hadits.member', $permissions);
        $this->assertNotContains('subject-group.tahfidz.member', $permissions);
    }

    public function test_koordinator_rumpun_dari_tugas_tambahan(): void
    {
        $resolver = app(SubjectGroupResolver::class);
        $umum = SubjectGroup::where('code', 'umum')->firstOrFail();

        $this->assertTrue($resolver->isCoordinatorOf($this->koordinatorUmum, $umum));
        $this->assertFalse($resolver->isCoordinatorOf($this->guruMath, $umum));

        $permissions = collect(app(SubjectGroupPermissionProvider::class)->provide($this->koordinatorUmum->id))
            ->pluck('permission')
            ->all();

        $this->assertContains('subject-group.umum.coordinator', $permissions);
        $this->assertSame([], $resolver->groupsForTeacher($this->koordinatorUmum)->all(), 'Koordinator murni tidak otomatis menjadi member pengampu.');
    }

    // ─────────────────────────────────────────────────────────────
    // REVIEWER SERUMPUN
    // ─────────────────────────────────────────────────────────────

    public function test_koordinator_rumpun_menjadi_reviewer_utama(): void
    {
        $soal = $this->makeSoal($this->bankMath(), $this->guruMath, 'Berapa hasil 12 x 3?');

        $reviewers = app(ReviewWorkflowService::class)->resolveSerumpunReviewers($soal, $this->guruMath);

        $this->assertNotEmpty($reviewers);
        $this->assertSame($this->koordinatorUmum->id, $reviewers[0], 'Koordinator rumpun harus menjadi reviewer utama.');
        $this->assertContains($this->guruMath2->id, $reviewers, 'Guru mapel sama tetap menjadi reviewer.');
        $this->assertNotContains($this->guruFisika->id, $reviewers, 'Guru mapel lain (rumpun sama) tidak dipakai selama ada guru mapel yang sama.');
        $this->assertNotContains($this->guruMath->id, $reviewers, 'Penulis tidak boleh menjadi reviewer.');
    }

    public function test_fallback_rumpun_dipakai_saat_tidak_ada_guru_mapel_sama(): void
    {
        $ski = Subject::where('name', 'SKI')->firstOrFail();
        $this->assertSame('agama', $ski->subjectGroup->code);

        $soal = $this->makeSoal($this->makeBank($ski), $this->guruSki, 'Kapan Nabi hijrah ke Madinah?');

        $reviewers = app(ReviewWorkflowService::class)->resolveSerumpunReviewers($soal, $this->guruSki);

        // Tidak ada guru SKI lain → fallback guru rumpun Agama (Fiqih).
        $this->assertContains($this->guruFiqih->id, $reviewers);
        $this->assertNotContains($this->guruMath->id, $reviewers);
    }

    // ─────────────────────────────────────────────────────────────
    // GUARD PUBLISH PAKET
    // ─────────────────────────────────────────────────────────────

    public function test_paket_draft_tidak_dapat_dipublikasikan_sebelum_approved(): void
    {
        $paket = $this->makePaket();

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/paket-soal/{$paket->id}/publish")
            ->assertStatus(302)
            ->assertSessionHas('error');

        $this->assertSame(PaketSoal::WORKFLOW_DRAFT, $paket->fresh()->workflow_status);
        $this->assertFalse((bool) $paket->fresh()->is_published);
    }

    public function test_paket_approved_dapat_dipublikasikan(): void
    {
        $paket = $this->makePaket();
        $paket->forceFill([
            'workflow_status' => PaketSoal::WORKFLOW_APPROVED,
            'approved_at' => now(),
        ])->save();

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/paket-soal/{$paket->id}/publish")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $paket->refresh();
        $this->assertSame(PaketSoal::WORKFLOW_PUBLISHED, $paket->workflow_status);
        $this->assertTrue($paket->isFinal());
    }

    // ─────────────────────────────────────────────────────────────
    // MASKING KUNCI PER SISI
    // ─────────────────────────────────────────────────────────────

    public function test_compare_masking_kunci_dihitung_per_sisi(): void
    {
        $soalSaya = $this->makeSoal($this->bankMath(), $this->tu, 'Soal milik TU dengan kunci rahasia.', 'approved');
        $soalLain = $this->makeSoal($this->makeBank(Subject::where('name', 'Fisika')->firstOrFail()), $this->guruFisika, 'Soal milik guru fisika dengan kunci rahasia.', 'approved');

        // TU adalah penulis soal pertama (bukan soal kedua).
        $this->actingAs($this->tu)
            ->getJson("/{$this->tu->id}/bank-soal-terpusat/{$soalSaya->id}/compare/{$soalLain->id}")
            ->assertOk()
            ->assertJsonPath('soal.options.0.correct', true)
            ->assertJsonPath('compared.options.0.correct', null)
            ->assertJsonPath('compared.pembahasan', null);

        // Pihak ketiga: kedua sisi tertutup.
        $this->actingAs($this->guruFiqih)
            ->getJson("/{$this->guruFiqih->id}/bank-soal-terpusat/{$soalSaya->id}/compare/{$soalLain->id}")
            ->assertOk()
            ->assertJsonPath('solution_visible', false)
            ->assertJsonPath('soal.options.0.correct', null)
            ->assertJsonPath('compared.options.0.correct', null);
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE & HELPERS
    // ─────────────────────────────────────────────────────────────

    private function bankMath(): BankSoal
    {
        return BankSoal::where('subject_id', Subject::where('name', 'Matematika')->value('id'))
            ->where('school_id', $this->school->id)
            ->firstOrFail();
    }

    private function makeBank(Subject $subject): BankSoal
    {
        return BankSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'nama' => 'Bank '.$subject->name,
            'jenis_soal' => 'pilihan_ganda',
            'tingkat_kesulitan_target' => 'campuran',
            'shared_scope' => 'public_pool',
            'is_central' => true,
            'owner_user_id' => $this->guruMath->id,
            'created_by' => $this->guruMath->id,
        ]);
    }

    private function makeSoal(BankSoal $bank, User $author, string $question, string $workflow = 'draft'): Soal
    {
        $engine = app(ContentHashEngine::class);

        $soal = Soal::create([
            'bank_soal_id' => $bank->id,
            'tipe_soal' => 'pg',
            'pertanyaan' => '<p>'.$question.'</p>',
            'pembahasan' => 'Pembahasan rahasia '.$question,
            'materi' => 'Materi Uji',
            'bobot_default' => 1,
            'tingkat_kesulitan_estimasi' => 'sedang',
            'waktu_estimasi_menit' => 2,
            'status' => $workflow === 'approved' ? 'approved' : 'draft',
            'workflow_status' => $workflow,
            'dibuat_oleh' => $author->id,
            'content_hash' => $engine->hashFromSoal($question, ['100']),
            'shingles_hash' => $engine->shinglesFromSoal($question),
            'approved_at' => $workflow === 'approved' ? now() : null,
        ]);

        SoalOption::create(['soal_id' => $soal->id, 'label' => 'A', 'teks_opsi' => '100', 'is_correct' => true, 'urutan' => 1]);
        SoalOption::create(['soal_id' => $soal->id, 'label' => 'B', 'teks_opsi' => '120', 'is_correct' => false, 'urutan' => 2]);

        return $soal;
    }

    private function makePaket(): PaketSoal
    {
        $subject = Subject::where('name', 'Matematika')->firstOrFail();

        $kisi = KisiKisiSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'created_by' => $this->tu->id,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi Uji Rumpun',
            'tingkat_sekolah' => 'smp',
            'total_soal_target' => 1,
            'is_active' => true,
        ]);

        $soal = $this->makeSoal($this->bankMath(), $this->tu, 'Soal paket untuk uji publish.', 'approved');

        $paket = PaketSoal::create([
            'kisi_kisi_soal_id' => $kisi->id,
            'judul' => 'Paket Uji Rumpun',
            'is_acak_urutan_soal' => true,
            'is_acak_opsi' => true,
            'waktu_pengerjaan_menit' => 60,
            'shared_scope' => 'internal_school',
            'kkm' => 70,
        ]);

        PaketSoalItem::create([
            'paket_soal_id' => $paket->id,
            'soal_id' => $soal->id,
            'urutan' => 1,
        ]);

        $paket->recomputeTotals();

        return $paket->fresh();
    }

    private function makeUser(string $name, string $email, string $jabatan = 'Guru Mapel'): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $this->seedSnapshot($user);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => $jabatan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $user;
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Rumpun',
            'code' => 'UURP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create(['work_unit_id' => $workUnitId, 'npsn' => '44445555', 'name' => 'Sekolah Rumpun Uji']);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group7a = StudyGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->grade->id, 'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true]);
        $this->group7b = StudyGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->grade->id, 'name' => '7B', 'code' => '7B', 'capacity' => 30, 'is_active' => true]);
        $this->group8a = StudyGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->grade->id, 'name' => '8A', 'code' => '8A', 'capacity' => 30, 'is_active' => true]);

        $math = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);
        $fisika = Subject::create(['school_id' => $this->school->id, 'code' => 'FIS', 'name' => 'Fisika', 'credit_hours' => 3, 'is_active' => true]);
        $fiqih = Subject::create(['school_id' => $this->school->id, 'code' => 'FQH', 'name' => 'Fiqih', 'credit_hours' => 2, 'is_active' => true]);
        $ski = Subject::create(['school_id' => $this->school->id, 'code' => 'SKI', 'name' => 'SKI', 'credit_hours' => 2, 'is_active' => true]);
        $hadits = Subject::create(['school_id' => $this->school->id, 'code' => 'HDS', 'name' => 'Hadits Arbain', 'credit_hours' => 2, 'is_active' => true]);

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/RUMPUN/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Rumpun',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->guruMath = $this->makeUser('Guru Matematika', 'guru.math@rumpun.test');
        $this->guruMath2 = $this->makeUser('Guru Matematika 2', 'guru.math2@rumpun.test');
        $this->guruFisika = $this->makeUser('Guru Fisika', 'guru.fisika@rumpun.test');
        $this->guruFiqih = $this->makeUser('Guru Fiqih', 'guru.fiqih@rumpun.test');
        $this->guruSki = $this->makeUser('Guru SKI', 'guru.ski@rumpun.test');
        $this->koordinatorUmum = $this->makeUser('Koordinator Guru Umum', 'koor.umum@rumpun.test', 'Guru Mapel');
        $this->tu = $this->makeUser('TU Rumpun', 'tu.rumpun@rumpun.test', 'Staf Tata Usaha');

        $assign = function (User $teacher, Subject $subject, StudyGroup $group) use ($decreeId) {
            TeachingAssignment::create([
                'decree_id' => $decreeId,
                'teacher_id' => $teacher->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $group->id,
                'subject_id' => $subject->id,
                'weekly_hours' => 2,
                'status' => 'active',
            ]);
        };

        // Multi-penugasan: matematika di dua kelas + hadits; fisika beda guru.
        $assign($this->guruMath, $math, $this->group7a);
        $assign($this->guruMath, $math, $this->group7b);
        $assign($this->guruMath, $hadits, $this->group7a);
        $assign($this->guruMath2, $math, $this->group7a);
        $assign($this->guruFisika, $fisika, $this->group8a);
        $assign($this->guruFiqih, $fiqih, $this->group7a);
        $assign($this->guruSki, $ski, $this->group7b);

        // Koordinator rumpun via tugas tambahan resmi.
        GtkAdditionalTask::create([
            'user_id' => $this->koordinatorUmum->id,
            'work_unit_id' => $workUnitId,
            'nama_tugas' => 'Koordinator Guru Umum',
            'hours_per_week' => 2,
        ]);

        // Bank matematika utama (dipakai reviewer test).
        BankSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $math->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'nama' => 'Bank Matematika Rumpun',
            'jenis_soal' => 'pilihan_ganda',
            'tingkat_kesulitan_target' => 'campuran',
            'shared_scope' => 'public_pool',
            'is_central' => true,
            'owner_user_id' => $this->guruMath->id,
            'created_by' => $this->guruMath->id,
        ]);
    }

    private function seedSnapshot(User $user): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
