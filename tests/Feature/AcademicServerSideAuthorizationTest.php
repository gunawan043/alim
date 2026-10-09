<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\KisiKisiSoal;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\Role;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Evaluasi\ContentHashEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Increment 2 — Sweep guard server-side (kebijakan Tahap 1):
 * batas satuan pendidikan, kewenangan paket/kisi/bank soal, scope buku nilai,
 * ekspor kehadiran, dan API internal.
 */
class AcademicServerSideAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $groupA1;

    private Subject $mathA;

    private Subject $mathB;

    private User $guruA;

    private User $guruC;

    private User $guruB;

    private User $kurikulum;

    private User $tu;

    private BankSoal $bankA;

    private BankSoal $bankB;

    private Soal $soalA;

    private Student $studentA;

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
    // PAKET & KISI
    // ─────────────────────────────────────────────────────────────

    public function test_paket_sekolah_lain_tidak_dapat_dibuka_dan_diubah(): void
    {
        $paket = $this->makePaket();

        // Guru sekolah B: paket sekolah A → 404.
        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/paket-soal/{$paket->id}")
            ->assertStatus(404);

        $this->actingAs($this->guruB)
            ->post("/{$this->guruB->id}/paket-soal/{$paket->id}/publish")
            ->assertStatus(404);
    }

    public function test_guru_lain_sekolah_sama_tidak_dapat_mengubah_paket_orang_lain(): void
    {
        $paket = $this->makePaket();

        // guruC: guru mapel sama di sekolah yang sama, bukan penyusun & bukan tim.
        $this->actingAs($this->guruC)
            ->post("/{$this->guruC->id}/paket-soal/{$paket->id}/reroll")
            ->assertStatus(403);

        $this->actingAs($this->guruC)
            ->delete("/{$this->guruC->id}/paket-soal/{$paket->id}")
            ->assertStatus(403);

        // Penyusun tetap boleh (reroll).
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/reroll")
            ->assertStatus(302);
    }

    public function test_kisi_sekolah_lain_tidak_dapat_dibuka(): void
    {
        $kisi = $this->makeKisi();

        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/kisi-kisi-soal/{$kisi->id}")
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────
    // BANK SOAL & REPOSITORI
    // ─────────────────────────────────────────────────────────────

    public function test_bank_privat_sekolah_lain_tidak_dapat_diakses(): void
    {
        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/bank-soal/{$this->bankA->id}")
            ->assertStatus(403);

        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/bank-soal/{$this->bankA->id}/soal")
            ->assertStatus(403);
    }

    public function test_submit_review_soal_bank_lain_ditolak(): void
    {
        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Soal privat sekolah A.');

        $this->actingAs($this->guruB)
            ->post("/{$this->guruB->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}/submit-review")
            ->assertStatus(403);

        // Penyusun sendiri tetap bisa.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}/submit-review")
            ->assertStatus(302);
    }

    public function test_reuse_soal_lintas_sekolah_ditolak(): void
    {
        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Soal untuk uji reuse lintas sekolah.');

        $this->actingAs($this->guruB)
            ->post("/{$this->guruB->id}/bank-soal-terpusat/{$soal->id}/reuse")
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // BUKU NILAI (trait scope langsung)
    // ─────────────────────────────────────────────────────────────

    public function test_scope_buku_nilai_mengikuti_penugasan_dan_sekolah(): void
    {
        $book = TeacherAdminBook::create([
            'teacher_id' => $this->guruA->id,
            'subject_id' => $this->mathA->id,
            'study_group_id' => $this->groupA1->id,
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $probe = new class extends Controller
        {
            use AuthorizesAcademicScope;

            public function check(Request $request, TeacherAdminBook $book): void
            {
                $this->authorizeAdminBookScope($request, $book);
            }
        };

        // guruB (sekolah lain) → 404
        [$status, ] = $this->probeStatus($probe, $this->guruB, $this->schoolB->id, $book);
        $this->assertSame(404, $status);

        // guruC (sekolah sama, bukan pengampu) → 403
        [$status, ] = $this->probeStatus($probe, $this->guruC, $this->schoolA->id, $book);
        $this->assertSame(403, $status);

        // guruA (pemilik buku) → lolos
        [$status, ] = $this->probeStatus($probe, $this->guruA, $this->schoolA->id, $book);
        $this->assertSame(0, $status);

        // Co-teacher pada mapel+rombel+TA sama → lolos
        TeachingAssignment::create([
            'decree_id' => $this->decreeId(),
            'teacher_id' => $this->guruC->id,
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->groupA1->id,
            'subject_id' => $this->mathA->id,
            'weekly_hours' => 2,
            'status' => 'active',
        ]);

        [$status, ] = $this->probeStatus($probe, $this->guruC, $this->schoolA->id, $book);
        $this->assertSame(0, $status);
    }

    // ─────────────────────────────────────────────────────────────
    // KEHADIRAN & API INTERNAL
    // ─────────────────────────────────────────────────────────────

    public function test_ekspor_kehadiran_santri_sekolah_lain_dan_bukan_wali_ditolak(): void
    {
        // guruB mengakses santri sekolah A → 404 (scope sekolah).
        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/absensi/harian/{$this->studentA->id}/export")
            ->assertStatus(404);

        // guruC: sekolah sama tetapi bukan wali kelas santri → 403.
        $this->actingAs($this->guruC)
            ->get("/{$this->guruC->id}/absensi/harian/{$this->studentA->id}/export")
            ->assertStatus(403);
    }

    public function test_rekap_kehadiran_rombel_sekolah_lain_ditolak(): void
    {
        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/absensi/harian/recap?study_group_id={$this->groupA1->id}")
            ->assertStatus(404);
    }

    public function test_api_grade_levels_lintas_sekolah_ditolak(): void
    {
        $this->actingAs($this->guruB)
            ->getJson("/{$this->guruB->id}/api/grade-levels/by-school/{$this->schoolA->id}")
            ->assertStatus(403);

        $this->actingAs($this->guruB)
            ->getJson("/{$this->guruB->id}/api/grade-levels/by-school/{$this->schoolB->id}")
            ->assertOk();
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{0:int,1:?HttpException}
     */
    private function probeStatus(object $probe, User $user, string $schoolId, TeacherAdminBook $book): array
    {
        $request = Request::create('/', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('schoolContextId', $schoolId);

        try {
            $probe->check($request, $book);

            return [0, null];
        } catch (HttpException $e) {
            return [$e->getStatusCode(), $e];
        }
    }

    private function makeKisi(): KisiKisiSoal
    {
        return KisiKisiSoal::create([
            'school_id' => $this->schoolA->id,
            'subject_id' => $this->mathA->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'created_by' => $this->guruA->id,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi Uji Guard',
            'tingkat_sekolah' => 'smp',
            'total_soal_target' => 1,
            'is_active' => true,
        ]);
    }

    private function makePaket(): PaketSoal
    {
        $kisi = $this->makeKisi();
        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Soal paket guard.', 'approved');

        $paket = PaketSoal::create([
            'kisi_kisi_soal_id' => $kisi->id,
            'judul' => 'Paket Uji Guard',
            'is_acak_urutan_soal' => true,
            'is_acak_opsi' => true,
            'waktu_pengerjaan_menit' => 60,
            'shared_scope' => 'internal_school',
            'kkm' => 70,
        ]);

        PaketSoalItem::create(['paket_soal_id' => $paket->id, 'soal_id' => $soal->id, 'urutan' => 1]);
        $paket->recomputeTotals();

        return $paket->fresh();
    }

    private function makeSoal(BankSoal $bank, User $author, string $question, string $workflow = 'draft'): Soal
    {
        $engine = app(ContentHashEngine::class);

        $soal = Soal::create([
            'bank_soal_id' => $bank->id,
            'tipe_soal' => 'pg',
            'pertanyaan' => '<p>'.$question.'</p>',
            'pembahasan' => 'Pembahasan '.$question,
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

    private function makeUser(string $name, string $email, School $school, string $jabatan = 'Guru Mapel'): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $this->seedSnapshot($user, $school);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $school->id,
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

    private function decreeId(): string
    {
        return $this->decreeId;
    }

    private string $decreeId;

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Guard',
            'code' => 'UUGD',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolA = School::create(['work_unit_id' => $workUnitId, 'npsn' => '77778888', 'name' => 'Sekolah Guard A']);
        $this->schoolB = School::create(['work_unit_id' => $workUnitId, 'npsn' => '99990000', 'name' => 'Sekolah Guard B']);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create(['school_id' => $this->schoolA->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);

        $this->groupA1 = StudyGroup::create(['school_id' => $this->schoolA->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->grade->id, 'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true]);

        $this->mathA = Subject::create(['school_id' => $this->schoolA->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);
        $this->mathB = Subject::create(['school_id' => $this->schoolB->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);

        $this->decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $this->decreeId,
            'decree_number' => 'TEST/GUARD/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Guard',
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->guruA = $this->makeUser('Guru A Guard', 'guru.a.guard@test.local', $this->schoolA);
        $this->guruC = $this->makeUser('Guru C Guard', 'guru.c.guard@test.local', $this->schoolA);
        $this->guruB = $this->makeUser('Guru B Guard', 'guru.b.guard@test.local', $this->schoolB);
        $this->kurikulum = $this->makeUser('Kurikulum Guard', 'kurikulum.guard@test.local', $this->schoolA, 'Koordinator Kurikulum');
        $this->tu = $this->makeUser('TU Guard', 'tu.guard@test.local', $this->schoolA, 'Staf Tata Usaha');

        TeachingAssignment::create([
            'decree_id' => $this->decreeId,
            'teacher_id' => $this->guruA->id,
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->groupA1->id,
            'subject_id' => $this->mathA->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        $this->bankA = BankSoal::create([
            'school_id' => $this->schoolA->id,
            'subject_id' => $this->mathA->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'nama' => 'Bank Privat Guru A',
            'jenis_soal' => 'pilihan_ganda',
            'tingkat_kesulitan_target' => 'campuran',
            'shared_scope' => 'private',
            'is_public' => false,
            'is_central' => false,
            'owner_user_id' => $this->guruA->id,
            'created_by' => $this->guruA->id,
        ]);

        $this->bankB = BankSoal::create([
            'school_id' => $this->schoolB->id,
            'subject_id' => $this->mathB->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'nama' => 'Bank Privat Guru B',
            'jenis_soal' => 'pilihan_ganda',
            'tingkat_kesulitan_target' => 'campuran',
            'shared_scope' => 'private',
            'is_public' => false,
            'is_central' => false,
            'owner_user_id' => $this->guruB->id,
            'created_by' => $this->guruB->id,
        ]);

        $this->soalA = $this->makeSoal($this->bankA, $this->guruA, 'Soal sekolah A untuk guard.');

        $this->studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'nisn' => '777000999',
            'name' => 'Santri Guard A',
            'gender' => 'L',
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->studentA->id,
            'study_group_id' => $this->groupA1->id,
            'academic_year_id' => $this->ay->id,
            'is_active' => true,
            'join_date' => '2026-07-15',
            'attendance_number' => 1,
        ]);
    }

    private function seedSnapshot(User $user, School $school): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
