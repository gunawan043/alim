<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Bank soal, input soal, dan impor hanya boleh memakai mata pelajaran dan
 * kelas yang diamanahkan SK Pembagian Tugas yang sedang AKTIF.
 */
class BankSoalSkScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $math;

    private Subject $ipa;

    private User $guru;

    private InstitutionDecree $decree;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        \DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Yayasan Uji Bank Soal',
            'code' => 'YBS',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'name' => 'SD IT Putra',
            'npsn' => '12345678',
            'jenjang' => 'SD',
            'is_active' => true,
        ]);

        $this->ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->grade = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => 'Kelas 4',
            'level' => 4,
        ]);

        $this->group = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '4A',
            'is_active' => true,
        ]);

        $this->math = Subject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => 'Matematika',
            'code' => 'MAT',
            'is_active' => true,
        ]);

        $this->ipa = Subject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => 'Ilmu Pengetahuan Alam',
            'code' => 'IPA',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Guru Matematika',
            'email' => 'guru.matematika@example.test',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $this->guru->assignRole($role);

        $this->seedSnapshot($this->guru, $this->school);

        \DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'nupy' => '1112223334',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Mapel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->decree = $this->makeDecree('111/PAH/SDIT-Pa/SK/VII/2026', InstitutionDecree::STATUS_ACTIVE);
        $this->assign($this->math, $this->decree);
    }

    public function test_form_bank_soal_hanya_menampilkan_mapel_di_sk_aktif(): void
    {
        $response = $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/create")
            ->assertOk();

        $response->assertSee('Matematika');
        $response->assertDontSee('Ilmu Pengetahuan Alam');
        $response->assertSee('111/PAH/SDIT-Pa/SK/VII/2026');
    }

    public function test_mapel_di_luar_sk_ditolak_saat_menyimpan_bank(): void
    {
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/bank-soal", [
                'nama' => 'Bank IPA',
                'subject_id' => $this->ipa->id,
                'jenis_soal' => 'pilihan_ganda',
                'tingkat_kesulitan_target' => 'sedang',
                'shared_scope' => 'private',
            ])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseMissing('bank_soal', ['nama' => 'Bank IPA']);
    }

    public function test_mapel_di_sk_diterima_saat_menyimpan_bank(): void
    {
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/bank-soal", [
                'nama' => 'Bank Matematika',
                'subject_id' => $this->math->id,
                'grade_level_id' => $this->grade->id,
                'jenis_soal' => 'pilihan_ganda',
                'tingkat_kesulitan_target' => 'sedang',
                'shared_scope' => 'private',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bank_soal', [
            'nama' => 'Bank Matematika',
            'subject_id' => $this->math->id,
        ]);
    }

    public function test_sk_terarsip_tidak_lagi_memberi_akses_mapel(): void
    {
        $this->decree->update(['status' => InstitutionDecree::STATUS_ARCHIVED]);

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/create")
            ->assertOk()
            ->assertViewHas('scope', fn ($scope) => $scope['has_assignment'] === false);
    }

    public function test_soal_saya_menampilkan_mapel_sk_dan_menolak_mapel_lain(): void
    {
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/soal-saya/create")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Ilmu Pengetahuan Alam');

        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/soal-saya", [
                'subject_id' => $this->ipa->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'jenis_asesmen' => 'harian',
                'tipe_soal' => 'uraian',
                'pertanyaan' => 'Sebutkan contoh hewan mamalia.',
                'tingkat_kesulitan_estimasi' => 'sedang',
            ])
            ->assertSessionHasErrors('subject_id');
    }

    public function test_impor_word_menolak_mapel_di_luar_sk(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'soal.docx',
            'bukan docx asli'
        );

        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/soal-saya/import-word", [
                'file' => $file,
                'subject_id' => $this->ipa->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'jenis_asesmen' => 'harian',
            ])
            ->assertSessionHasErrors('subject_id');
    }

    public function test_penugasan_di_sk_lain_tidak_membuka_mapel(): void
    {
        // Mapel IPA hanya diamanahkan SK draf, bukan SK aktif.
        $draft = $this->makeDecree('112/PAH/SDIT-Pa/SK/VII/2026', InstitutionDecree::STATUS_DRAFT);
        $this->assign($this->ipa, $draft);

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/create")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Ilmu Pengetahuan Alam');
    }

    public function test_tabel_pivot_bank_soal_tp_ada_di_sqlite(): void
    {
        // Migrasi asli melewati SQLite sehingga tabel ini hilang di produksi
        // dan halaman detail bank soal gagal 500.
        $this->assertTrue(Schema::hasTable('bank_soal_tp'));
    }

    public function test_halaman_detail_bank_soal_tidak_gagal(): void
    {
        $bank = BankSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'academic_year_id' => $this->ay->id,
            'nama' => 'Bank Matematika Kelas 4',
            'jenis_soal' => 'campuran',
            'bahasa' => 'latin',
            'tingkat_kesulitan_target' => 'campuran',
            'is_public' => false,
            'shared_scope' => 'internal_school',
            'owner_user_id' => $this->guru->id,
            'created_by' => $this->guru->id,
            'total_soal' => 0,
        ]);

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/{$bank->id}")
            ->assertOk()
            ->assertSee('Bank Matematika Kelas 4');
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

        \DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode([]),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }

    private function makeDecree(string $number, string $status): InstitutionDecree
    {
        return InstitutionDecree::create([
            'decree_number' => $number,
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'Pembagian Tugas Mengajar',
            'academic_year_id' => $this->ay->id,
            'school_id' => $this->school->id,
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-01',
            'status' => $status,
        ]);
    }

    private function assign(Subject $subject, InstitutionDecree $decree): void
    {
        TeachingAssignment::create([
            'decree_id' => $decree->id,
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $subject->id,
            'role' => 'guru_mapel',
            'weekly_hours' => 6,
            'status' => 'active',
        ]);
    }
}
