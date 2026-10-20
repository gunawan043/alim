<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\KisiKisiSoal;
use App\Models\PaketSoal;
use App\Models\PaketSoalDistribution;
use App\Models\PaketSoalItem;
use App\Models\ReviewAssignment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Evaluasi\ContentHashEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tahap 2 — Paket Soal sebagai NASKAH:
 *  - Komposisi eksplisit (jenis/jumlah/bobot/urutan) + generate butir.
 *  - Dukungan isian & uraian/esai.
 *  - Snapshot butir saat final (kebal perubahan soal sumber).
 *  - State machine transisi paket.
 *  - Distribusi fase `received`.
 */
class PaketNaskahPolicyTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $studyGroup;

    private Subject $math;

    private User $guruA;

    private User $guruB;

    private User $tu;

    private BankSoal $bank;

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
    // KOMPOSISI
    // ─────────────────────────────────────────────────────────────

    public function test_komposisi_tersimpan_dan_divalidasi_sebelum_approval(): void
    {
        $soal = $this->makeSoal($this->bank, 'pg', 'Hasil 2 + 2 adalah ....', 'approved');
        $paket = $this->makePaket([$soal]);

        // Komposisi sesuai isi paket.
        $this->actingAs($this->guruA)
            ->put("/{$this->guruA->id}/paket-soal/{$paket->id}/komposisi", [
                'komposisi' => [['jenis' => 'pg', 'jumlah' => 1, 'bobot' => 1]],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertSame('pg', $paket->fresh()->komposisi[0]['jenis']);

        // Komposisi tidak terpenuhi → submit approval ditolak.
        $this->actingAs($this->guruA)
            ->put("/{$this->guruA->id}/paket-soal/{$paket->id}/komposisi", [
                'komposisi' => [['jenis' => 'pg', 'jumlah' => 2, 'bobot' => 1]],
            ])
            ->assertStatus(302);

        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/submit-approval")
            ->assertStatus(302)
            ->assertSessionHas('error');

        $this->assertSame(PaketSoal::WORKFLOW_DRAFT, $paket->fresh()->workflow_status);

        // Diperbaiki → approval berjalan.
        $this->actingAs($this->guruA)
            ->put("/{$this->guruA->id}/paket-soal/{$paket->id}/komposisi", [
                'komposisi' => [['jenis' => 'pg', 'jumlah' => 1, 'bobot' => 1]],
            ])
            ->assertStatus(302);

        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/submit-approval")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertSame(PaketSoal::WORKFLOW_REVIEW, $paket->fresh()->workflow_status);
    }

    public function test_generate_dari_komposisi_mendukung_isian_dan_uraian(): void
    {
        $this->makeSoal($this->bank, 'pg', 'Pilih satu jawaban benar.', 'approved');
        $this->makeSoal($this->bank, 'isian', 'Isi titik-titik berikut.', 'approved');
        $this->makeSoal($this->bank, 'uraian', 'Uraikan pendapatmu.', 'approved');

        $paket = $this->makePaket([]);

        $this->actingAs($this->guruA)
            ->put("/{$this->guruA->id}/paket-soal/{$paket->id}/komposisi", [
                'komposisi' => [
                    ['jenis' => 'pg', 'jumlah' => 1, 'bobot' => 1],
                    ['jenis' => 'isian', 'jumlah' => 1, 'bobot' => 2],
                    ['jenis' => 'uraian', 'jumlah' => 1, 'bobot' => 5],
                ],
            ])
            ->assertStatus(302);

        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/komposisi/generate")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $items = $paket->fresh()->items()->orderBy('urutan')->get();

        $this->assertCount(3, $items);
        $this->assertSame(['pg', 'isian', 'uraian'], $items->pluck('jenis_soal')->all());
        $this->assertSame([1, 2, 3], $items->pluck('nomor')->all());
        $this->assertSame([1.0, 2.0, 5.0], $items->map(fn ($i) => (float) $i->bobot_override)->all());

        // Komposisi terpenuhi setelah generate.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/submit-approval")
            ->assertStatus(302)
            ->assertSessionHas('success');
    }

    // ─────────────────────────────────────────────────────────────
    // SNAPSHOT NASKAH
    // ─────────────────────────────────────────────────────────────

    public function test_publish_membekukan_butir_dan_kebal_perubahan_soal(): void
    {
        $soal = $this->makeSoal($this->bank, 'pg', 'Ibu kota Indonesia adalah ....', 'approved');
        $paket = $this->makePaket([$soal]);

        $paket->forceFill([
            'workflow_status' => PaketSoal::WORKFLOW_APPROVED,
            'approved_at' => now(),
        ])->save();

        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/publish")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $paket->refresh();
        $item = $paket->items()->firstOrFail();

        $this->assertNotNull($paket->snapshot_at, 'Publish membekukan butir.');
        $this->assertTrue($paket->hasSnapshot());
        $this->assertStringContainsString('Ibu kota Indonesia', (string) ($item->konten_snapshot['pertanyaan'] ?? ''));
        $this->assertSame('pg', $item->konten_snapshot['jenis']);

        // Perubahan soal sumber setelah final tidak mengubah naskah.
        $soal->forceFill(['pertanyaan' => '<p>PERTANYAAN DIUBAH SETELAH FINAL</p>'])->save();
        $item->refresh();

        $konten = $item->kontenFinal();
        $this->assertStringContainsString('Ibu kota Indonesia', (string) $konten['pertanyaan']);
        $this->assertStringNotContainsString('DIUBAH', (string) $konten['pertanyaan']);

        // Publish ulang ditolak.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/publish")
            ->assertSessionHas('error');
    }

    // ─────────────────────────────────────────────────────────────
    // STATE MACHINE
    // ─────────────────────────────────────────────────────────────

    public function test_state_machine_menjaga_transisi_paket(): void
    {
        $soal = $this->makeSoal($this->bank, 'pg', 'Soal uji transisi.', 'approved');
        $paket = $this->makePaket([$soal]);

        // Publish dari draft ditolak.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/publish")
            ->assertSessionHas('error');
        $this->assertFalse((bool) $paket->fresh()->is_published);

        // Paksa approved + publish + snapshot.
        $paket->forceFill(['workflow_status' => PaketSoal::WORKFLOW_APPROVED, 'approved_at' => now()])->save();
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/publish")
            ->assertStatus(302);
        $paket->refresh();
        $this->assertTrue($paket->is_published);

        // Paket final tidak dapat dihapus.
        $this->actingAs($this->guruA)
            ->delete("/{$this->guruA->id}/paket-soal/{$paket->id}")
            ->assertSessionHas('error');
        $this->assertNotNull(PaketSoal::find($paket->id));

        // Reroll setelah snapshot ditolak.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/reroll")
            ->assertSessionHas('error');
        $this->assertSame(1, $paket->fresh()->items()->count());

        // Unpublish mengembalikan ke Approved.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/unpublish")
            ->assertStatus(302);
        $paket->refresh();
        $this->assertFalse((bool) $paket->is_published);
        $this->assertSame(PaketSoal::WORKFLOW_APPROVED, $paket->workflow_status);

        // Ajukan approval ulang dari approved ditolak.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/submit-approval")
            ->assertSessionHas('error');
    }

    // ─────────────────────────────────────────────────────────────
    // DISTRIBUSI `RECEIVED`
    // ─────────────────────────────────────────────────────────────

    public function test_distribusi_ditandai_diterima_oleh_penerima(): void
    {
        $soal = $this->makeSoal($this->bank, 'pg', 'Soal distribusi.', 'approved');
        $paket = $this->makePaket([$soal]);

        $paket->forceFill([
            'workflow_status' => PaketSoal::WORKFLOW_APPROVED,
            'approved_at' => now(),
            'is_published' => true,
            'published_at' => now(),
        ])->save();

        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/paket-soal/{$paket->id}/distribute")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertSame(5, $paket->distributions()->count());

        $tuDistribution = $paket->distributions()
            ->where('recipient_user_id', $this->tu->id)
            ->firstOrFail();

        $this->assertSame(PaketSoalDistribution::STATUS_SENT, $tuDistribution->status);

        // Bukan penerima → 403.
        $this->actingAs($this->guruB)
            ->put("/{$this->guruB->id}/paket-soal/{$paket->id}/distribusi/{$tuDistribution->id}/terima")
            ->assertStatus(403);

        // Penerima menandai diterima.
        $this->actingAs($this->tu)
            ->put("/{$this->tu->id}/paket-soal/{$paket->id}/distribusi/{$tuDistribution->id}/terima")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $tuDistribution->refresh();
        $this->assertSame(PaketSoalDistribution::STATUS_RECEIVED, $tuDistribution->status);
        $this->assertNotNull($tuDistribution->received_at);
        $this->assertSame($this->tu->id, $tuDistribution->received_by);
    }

    public function test_kartu_soal_menampilkan_butir_dan_melindungi_kunci(): void
    {
        $soal = $this->makeSoal($this->bank, 'pg', 'Ibu kota Indonesia?', 'approved');
        $paket = $this->makePaket([$soal]);

        // Penyusun serumpun → kunci terlihat.
        $this->actingAs($this->guruA)
            ->get("/{$this->guruA->id}/paket-soal/{$paket->id}/kartu-soal")
            ->assertOk()
            ->assertSee('Kartu Soal')
            ->assertSee('Ibu kota Indonesia?')
            ->assertDontSee('Disembunyikan');

        // Halaman cetak langsung menghasilkan PDF.
        $cetak = $this->actingAs($this->guruA)
            ->get("/{$this->guruA->id}/paket-soal/{$paket->id}/kartu-soal/cetak");
        $cetak->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $cetak->getContent());

        // Peran bukan serumpun (TU) tetap dapat membuka kartu, tetapi kunci disamarkan.
        $this->actingAs($this->tu)
            ->get("/{$this->tu->id}/paket-soal/{$paket->id}/kartu-soal")
            ->assertOk()
            ->assertSee('Disembunyikan')
            ->assertDontSee('>A<', false);
    }

    public function test_dokumen_validasi_soal_menampilkan_pembuat_dan_validator(): void
    {
        $soal = $this->makeSoal($this->bank, 'pg', 'Soal untuk validasi?', 'approved');
        $paket = $this->makePaket([$soal]);

        ReviewAssignment::create([
            'reviewable_type' => Soal::class,
            'reviewable_id' => $soal->id,
            'reviewer_id' => $this->guruB->id,
            'status' => ReviewAssignment::STATUS_REVISION,
            'note' => 'Perbaiki opsi C — distraktor lemah.',
            'decided_at' => now(),
        ]);

        $this->actingAs($this->guruA)
            ->get("/{$this->guruA->id}/paket-soal/{$paket->id}/validasi-soal")
            ->assertOk()
            ->assertSee('VALIDASI SOAL')
            ->assertSee($this->guruB->name)
            ->assertSee('Perbaiki opsi C')
            ->assertSee('Soal yang perlu diperbaiki');

        $cetak = $this->actingAs($this->guruA)
            ->get("/{$this->guruA->id}/paket-soal/{$paket->id}/validasi-soal/cetak");
        $cetak->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $cetak->getContent());
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE & HELPERS
    // ─────────────────────────────────────────────────────────────

    private function makeSoal(BankSoal $bank, string $jenis, string $question, string $workflow = 'approved'): Soal
    {
        $engine = app(ContentHashEngine::class);

        $soal = Soal::create([
            'bank_soal_id' => $bank->id,
            'tipe_soal' => $jenis,
            'pertanyaan' => '<p>'.$question.'</p>',
            'pembahasan' => 'Pembahasan '.$question,
            'materi' => 'Materi Uji Naskah',
            'bobot_default' => $jenis === 'uraian' ? 5 : ($jenis === 'isian' ? 2 : 1),
            'tingkat_kesulitan_estimasi' => 'sedang',
            'waktu_estimasi_menit' => 2,
            'status' => $workflow === 'approved' ? 'approved' : 'draft',
            'workflow_status' => $workflow,
            'dibuat_oleh' => $this->guruA->id,
            'content_hash' => $engine->hashFromSoal($question, [$jenis]),
            'shingles_hash' => $engine->shinglesFromSoal($question),
            'approved_at' => $workflow === 'approved' ? now() : null,
        ]);

        if ($jenis === 'pg') {
            SoalOption::create(['soal_id' => $soal->id, 'label' => 'A', 'teks_opsi' => 'Jakarta', 'is_correct' => true, 'urutan' => 1]);
            SoalOption::create(['soal_id' => $soal->id, 'label' => 'B', 'teks_opsi' => 'Bandung', 'is_correct' => false, 'urutan' => 2]);
        }

        return $soal;
    }

    /**
     * @param  array<int, Soal>  $soals
     */
    private function makePaket(array $soals): PaketSoal
    {
        $kisi = KisiKisiSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'created_by' => $this->guruA->id,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi Naskah Uji',
            'tingkat_sekolah' => 'smp',
            'total_soal_target' => max(1, count($soals)),
            'is_active' => true,
        ]);

        $paket = PaketSoal::create([
            'kisi_kisi_soal_id' => $kisi->id,
            'judul' => 'Paket Naskah Uji',
            'is_acak_urutan_soal' => true,
            'is_acak_opsi' => true,
            'waktu_pengerjaan_menit' => 60,
            'shared_scope' => 'internal_school',
            'kkm' => 70,
        ]);

        foreach (array_values($soals) as $index => $soal) {
            PaketSoalItem::create([
                'paket_soal_id' => $paket->id,
                'soal_id' => $soal->id,
                'urutan' => $index + 1,
                'nomor' => $index + 1,
                'jenis_soal' => $soal->tipe_soal,
            ]);
        }

        $paket->recomputeTotals();

        return $paket->fresh();
    }

    private function makeUser(string $name, string $email, string $jabatan = 'Guru Mapel'): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
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
            'name' => 'Unit Uji Naskah',
            'code' => 'UU-NASKAH',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99990000',
            'name' => 'Sekolah Uji Naskah',
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

        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->math = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);

        $this->guruA = $this->makeUser('Guru Naskah A', 'guru.naskah.a@test.local');
        $this->guruB = $this->makeUser('Guru Naskah B', 'guru.naskah.b@test.local');
        $this->tu = $this->makeUser('Staf Tata Usaha', 'tu.naskah@test.local', 'Staf Tata Usaha');

        $this->decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $this->decreeId,
            'decree_number' => 'TEST/NASKAH/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Naskah',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$this->guruA, $this->guruB] as $teacher) {
            TeachingAssignment::create([
                'decree_id' => $this->decreeId,
                'teacher_id' => $teacher->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $this->studyGroup->id,
                'subject_id' => $this->math->id,
                'weekly_hours' => 4,
                'status' => 'active',
            ]);
        }

        $this->bank = BankSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'nama' => 'Bank Naskah Uji',
            'jenis_soal' => 'campuran',
            'tingkat_kesulitan_target' => 'campuran',
            'shared_scope' => 'public_pool',
            'is_central' => true,
            'owner_user_id' => $this->guruA->id,
            'created_by' => $this->guruA->id,
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
