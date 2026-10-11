<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\AlurTujuanPembelajaranItem;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\PerangkatPembelajaran;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tahap 2 — RPM sebagai dokumen berversi:
 *  - Satu dokumen CURRENT per konteks akademik (mapel + jenjang + TA + semester).
 *  - Approval final kolektif oleh seluruh anggota rumpun (bukan koordinator tunggal).
 *  - Final dikunci; revisi = versi baru (dokumen lama menjadi riwayat).
 */
class RpmVersionLockPolicyTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $math;

    private Subject $fiqih;

    private User $guru1;

    private User $guru2;

    private User $guru3;

    private User $guru4;

    private User $kurikulum;

    private string $decreeId;

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
    // SATU DOKUMEN PER KONTEKS
    // ─────────────────────────────────────────────────────────────

    public function test_satu_rpm_per_konteks_akademik(): void
    {
        $atp = $this->makeAtp($this->math);

        $this->actingAs($this->guru1)
            ->post("/{$this->guru1->id}/kurikulum/perangkat", [
                'atp_id' => $atp->id,
                'judul' => 'RPM Matematika Kelas 7',
                'tipe' => 'umum',
            ])
            ->assertStatus(302);

        $this->assertSame(1, PerangkatPembelajaran::count());
        $existing = PerangkatPembelajaran::firstOrFail();

        // Percobaan kedua pada konteks yang sama ditolak (diarahkan ke dokumen ada).
        $this->actingAs($this->guru1)
            ->post("/{$this->guru1->id}/kurikulum/perangkat", [
                'atp_id' => $atp->id,
                'judul' => 'RPM Duplikat',
                'tipe' => 'umum',
            ])
            ->assertStatus(302)
            ->assertRedirect(route('user.kurikulum.perangkat.show', [
                'userId' => $this->guru1->id,
                'id' => $existing->id,
            ]))
            ->assertSessionHas('error');

        $this->assertSame(1, PerangkatPembelajaran::count(), 'Satu RPM per konteks akademik.');
    }

    // ─────────────────────────────────────────────────────────────
    // APPROVAL KOLEKTIF → FINAL TERKUNCI → VERSI BARU
    // ─────────────────────────────────────────────────────────────

    public function test_approval_kolektif_rumpun_mengunci_rpm_dan_revisi_lewat_versi_baru(): void
    {
        $perangkat = $this->buatRpm();
        $workflow = app(\App\Services\Evaluasi\ReviewWorkflowService::class);

        $approvers = $workflow->resolveRumpunApprovers($perangkat, $this->guru1);

        // SELURUH anggota rumpun (kecuali pengaju) — bukan koordinator tunggal.
        $this->assertCount(2, $approvers);
        $this->assertContains($this->guru2->id, $approvers);
        $this->assertContains($this->guru4->id, $approvers);
        $this->assertNotContains($this->guru3->id, $approvers, 'Guru rumpun lain tidak menjadi approver.');
        $this->assertNotContains($this->guru1->id, $approvers, 'Pengaju tidak menjadi approver.');

        $this->actingAs($this->guru1)
            ->post("/{$this->guru1->id}/kurikulum/perangkat/{$perangkat->id}/submit-review")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $perangkat->refresh();
        $this->assertSame(PerangkatPembelajaran::WORKFLOW_REVIEW, $perangkat->workflow_status);

        // Semua approver harus menyetujui.
        foreach ($perangkat->reviewAssignments()->get() as $assignment) {
            $reviewer = User::findOrFail($assignment->reviewer_id);
            $this->actingAs($reviewer)
                ->post("/{$reviewer->id}/review-soal/{$assignment->id}/decide", ['status' => 'approved'])
                ->assertStatus(302);
        }

        $perangkat->refresh();
        $this->assertSame(PerangkatPembelajaran::WORKFLOW_APPROVED, $perangkat->workflow_status);
        $this->assertSame(PerangkatPembelajaran::STATUS_FINAL, $perangkat->status);
        $this->assertNotNull($perangkat->locked_at, 'Approval kolektif mengunci dokumen.');
        $this->assertFalse($perangkat->isEditable());

        // Final terkunci: update & hapus ditolak.
        $this->actingAs($this->guru1)
            ->put("/{$this->guru1->id}/kurikulum/perangkat/{$perangkat->id}", [
                'judul' => 'Mengubah dokumen terkunci',
                'status' => 'draft',
            ])
            ->assertStatus(403);

        $this->actingAs($this->guru1)
            ->delete("/{$this->guru1->id}/kurikulum/perangkat/{$perangkat->id}")
            ->assertStatus(403);

        // Revisi = versi baru; dokumen lama menjadi riwayat (non-current, tetap terkunci).
        $this->actingAs($this->guru1)
            ->post("/{$this->guru1->id}/kurikulum/perangkat/{$perangkat->id}/new-version")
            ->assertStatus(302);

        $perangkat->refresh();
        $baru = PerangkatPembelajaran::where('parent_id', $perangkat->id)->firstOrFail();

        $this->assertSame(2, $baru->version);
        $this->assertTrue((bool) $baru->is_current);
        $this->assertSame(PerangkatPembelajaran::WORKFLOW_DRAFT, $baru->workflow_status);
        $this->assertNull($baru->locked_at);
        $this->assertTrue($baru->isEditable());

        $this->assertFalse((bool) $perangkat->is_current);
        $this->assertNotNull($perangkat->locked_at);
        $this->assertSame(PerangkatPembelajaran::STATUS_FINAL, $perangkat->status);

        // Versi baru masih dapat diubah selama draft.
        $this->actingAs($this->guru1)
            ->put("/{$this->guru1->id}/kurikulum/perangkat/{$baru->id}", [
                'judul' => 'RPM Matematika Kelas 7 (revisi)',
                'tipe' => 'umum',
                'status' => 'draft',
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');
    }

    public function test_permintaan_perbaikan_tidak_mengunci_dan_dapat_disubmit_ulang(): void
    {
        $perangkat = $this->buatRpm();

        $this->actingAs($this->guru1)
            ->post("/{$this->guru1->id}/kurikulum/perangkat/{$perangkat->id}/submit-review")
            ->assertStatus(302);

        $assignment = $perangkat->reviewAssignments()->firstOrFail();

        $this->actingAs($this->guru2)
            ->post("/{$this->guru2->id}/review-soal/{$assignment->id}/decide", ['status' => 'revision'])
            ->assertStatus(302);

        $perangkat->refresh();
        $this->assertSame(PerangkatPembelajaran::WORKFLOW_REVISI, $perangkat->workflow_status);
        $this->assertNull($perangkat->locked_at, 'Perlu perbaikan tidak mengunci dokumen.');
        $this->assertTrue($perangkat->isEditable());

        // Penyusun memperbaiki lalu mengajukan ulang.
        $this->actingAs($this->guru1)
            ->put("/{$this->guru1->id}/kurikulum/perangkat/{$perangkat->id}", [
                'judul' => $perangkat->judul,
                'tipe' => $perangkat->tipe,
                'status' => 'draft',
                'desain' => ['pertanyaan_pemantik' => 'Sudah diperbaiki.'],
            ])
            ->assertStatus(302);

        $this->actingAs($this->guru1)
            ->post("/{$this->guru1->id}/kurikulum/perangkat/{$perangkat->id}/submit-review")
            ->assertStatus(302);

        $this->assertSame(
            PerangkatPembelajaran::WORKFLOW_REVIEW,
            $perangkat->fresh()->workflow_status
        );
    }

    public function test_tim_kurikulum_dapat_menetapkan_final_langsung_dan_terkunci(): void
    {
        $perangkat = $this->buatRpm();

        $this->actingAs($this->kurikulum)
            ->put("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}", [
                'judul' => 'RPM Diverifikasi Kurikulum',
                'tipe' => 'umum',
                'status' => 'final',
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $perangkat->refresh();
        $this->assertSame(PerangkatPembelajaran::STATUS_FINAL, $perangkat->status);
        $this->assertNotNull($perangkat->locked_at);
        $this->assertSame($this->kurikulum->id, $perangkat->locked_by);

        $this->actingAs($this->kurikulum)
            ->put("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}", [
                'judul' => 'Ubah lagi',
                'status' => 'draft',
            ])
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE & HELPERS
    // ─────────────────────────────────────────────────────────────

    private function buatRpm(): PerangkatPembelajaran
    {
        $atp = $this->makeAtp($this->math);

        $this->actingAs($this->guru1)
            ->post("/{$this->guru1->id}/kurikulum/perangkat", [
                'atp_id' => $atp->id,
                'judul' => 'RPM Matematika Kelas 7',
                'tipe' => 'umum',
            ])
            ->assertStatus(302);

        return PerangkatPembelajaran::firstOrFail();
    }

    private function makeAtp(Subject $subject): AlurTujuanPembelajaran
    {
        $cp = CapaianPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'fase' => 'D',
            'elemen' => 'Bilangan',
            'deskripsi' => 'CP '.$subject->name.' fase D.',
            'urutan' => 1,
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);

        $tp1 = TujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'fase' => 'D',
            'kode_tp' => 'TP.01',
            'deskripsi' => 'Memahami bilangan bulat.',
            'elemen' => 'Bilangan',
            'alokasi_waktu' => 40,
            'urutan' => 1,
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);

        $tp2 = TujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'fase' => 'D',
            'kode_tp' => 'TP.02',
            'deskripsi' => 'Menerapkan operasi aljabar.',
            'elemen' => 'Aljabar',
            'alokasi_waktu' => 20,
            'urutan' => 2,
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);

        $atp = AlurTujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $subject->id,
            'grade_level_id' => $this->grade->id,
            'fase' => 'D',
            'teacher_id' => $this->guru1->id,
            'status' => 'draft',
            'total_jp' => 0,
            'created_by' => $this->kurikulum->id,
        ]);

        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'tujuan_pembelajaran_id' => $tp1->id,
            'urutan' => 1,
            'jp_alokasi' => 40,
        ]);

        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'tujuan_pembelajaran_id' => $tp2->id,
            'urutan' => 2,
            'jp_alokasi' => 20,
        ]);

        $atp->recalculateTotal();

        return $atp->fresh();
    }

    private function makeUser(string $name, string $email, string $jabatan = 'Guru Mapel'): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

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
            'name' => 'Unit Uji RPM',
            'code' => 'UU-RPM',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '77778888',
            'name' => 'Sekolah Uji RPM',
        ]);

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

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->math = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);
        $this->fiqih = Subject::create(['school_id' => $this->school->id, 'code' => 'FQH', 'name' => 'Fiqih', 'credit_hours' => 2, 'is_active' => true]);

        // Pemetaan master rumpun eksplisit.
        $umum = SubjectGroup::where('code', 'umum')->firstOrFail();
        $agama = SubjectGroup::where('code', 'agama')->firstOrFail();
        $this->math->forceFill(['subject_group_id' => $umum->id])->saveQuietly();
        $this->fiqih->forceFill(['subject_group_id' => $agama->id])->saveQuietly();

        $this->guru1 = $this->makeUser('Guru Satu', 'guru1@rpm.test');
        $this->guru2 = $this->makeUser('Guru Dua', 'guru2@rpm.test');
        $this->guru3 = $this->makeUser('Guru Tiga', 'guru3@rpm.test');
        $this->guru4 = $this->makeUser('Guru Empat', 'guru4@rpm.test');
        $this->kurikulum = $this->makeUser('Koor Kurikulum RPM', 'kurikulum@rpm.test', 'Koordinator Kurikulum');

        $this->decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $this->decreeId,
            'decree_number' => 'TEST/RPM/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji RPM',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [$this->guru1, $this->math],
            [$this->guru2, $this->math],
            [$this->guru4, $this->math],
            [$this->guru3, $this->fiqih],
        ] as [$teacher, $subject]) {
            TeachingAssignment::create([
                'decree_id' => $this->decreeId,
                'teacher_id' => $teacher->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $this->group->id,
                'subject_id' => $subject->id,
                'weekly_hours' => 4,
                'status' => 'active',
            ]);
        }
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
