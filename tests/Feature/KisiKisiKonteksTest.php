<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\KisiKisiItem;
use App\Models\KisiKisiSoal;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Increment 2 — Kisi-kisi: konteks otomatis (mapel dari penugasan guru,
 * TA aktif, semester berjalan), validasi mapel di backend, dan normalisasi
 * level kognitif C6 (enum lama `C6_mencita`).
 */
class KisiKisiKonteksTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private Subject $matematika;

    private Subject $fisika;

    private StudyGroup $group;

    private User $guru;

    private User $kurikulum;

    private TujuanPembelajaran $tpMatematika;

    protected function setUp(): void
    {
        parent::setUp();

        // Layout sidebar memerlukan permission ini agar halaman dapat dirender.
        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Kisi', 'code' => 'UUK',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '77777777',
            'name' => 'Sekolah Uji Kisi',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'genap',
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

        $this->matematika = Subject::create([
            'school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika',
            'credit_hours' => 4, 'is_active' => true,
        ]);

        $this->fisika = Subject::create([
            'school_id' => $this->school->id, 'code' => 'FIS', 'name' => 'Fisika',
            'credit_hours' => 4, 'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->guru = $this->makeUser('Guru Kisi', 'guru.kisi@test.local', 'Guru Umum');
        $this->kurikulum = $this->makeUser('Koor Kurikulum Kisi', 'koor.kisi@test.local', 'Koordinator Kurikulum');

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/KISI/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Kisi',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Guru hanya mengampu Matematika (Fisika = mapel sekolah lain, bukan penugasannya).
        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->matematika->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        $this->tpMatematika = TujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->matematika->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'genap',
            'fase' => 'D',
            'kode_tp' => 'TP-1',
            'deskripsi' => 'Menyelesaikan operasi bilangan bulat.',
            'is_active' => true,
            'created_by' => $this->guru->id,
        ]);
    }

    public function test_form_kisi_kisi_mengikuti_penugasan_guru(): void
    {
        // Guru: hanya mapel penugasannya.
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/kisi-kisi-soal/create")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Fisika')
            // TA aktif otomatis terpilih + diberi keterangan (aktif).
            ->assertSee('value="'.$this->ay->id.'" selected', false)
            ->assertSee('(aktif)');

        // Kurikulum: seluruh mapel satuan pendidikan.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kisi-kisi-soal/create")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Fisika');
    }

    public function test_simpan_kisi_kisi_mapel_luar_penugasan_ditolak_dan_c6_dinormalisasi(): void
    {
        $payload = [
            'subject_id' => $this->matematika->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'genap',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi-kisi STS Matematika',
            'tingkat_sekolah' => 'smp',
            'total_soal_target' => 10,
            'items' => [[
                'tp_id' => $this->tpMatematika->id,
                'level_kognitif' => 'C6_mencipta',
                'materi' => 'Operasi bilangan bulat',
                'bentuk_soal' => 'pg',
                'nomor_soal' => '1-2',
                'jumlah_soal' => 2,
                'bobot_per_soal' => 5,
            ]],
        ];

        // Guru menyimpan mapel di luar penugasannya → ditolak backend.
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/kisi-kisi-soal", array_merge($payload, [
                'subject_id' => $this->fisika->id,
            ]))
            ->assertStatus(422);

        // Mapel penugasan → tersimpan; C6_mencipta dinormalisasi ke enum database.
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/kisi-kisi-soal", $payload)
            ->assertRedirect();

        $kisi = KisiKisiSoal::where('subject_id', $this->matematika->id)->first();
        $this->assertNotNull($kisi);
        $this->assertSame($this->school->id, $kisi->school_id);

        $item = KisiKisiItem::where('kisi_kisi_soal_id', $kisi->id)->first();
        $this->assertNotNull($item);
        $this->assertSame('C6_mencita', $item->level_kognitif);
        $this->assertSame('Operasi bilangan bulat', $item->materi);
        $this->assertSame('pg', $item->bentuk_soal);
        $this->assertSame('1-2', $item->nomor_soal);

        // Halaman detail menampilkan format kolom kebijakan:
        // No | Indikator | Materi | Bentuk Soal | Nomor Soal | Level Kognitif.
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/kisi-kisi-soal/{$kisi->id}")
            ->assertOk()
            ->assertSee('Bentuk Soal')
            ->assertSee('Nomor Soal')
            ->assertSee('Operasi bilangan bulat')
            ->assertSee('Pilihan Ganda');
    }

    public function test_halaman_edit_kisi_kisi_memuat_field_mapel_dan_update_berhasil(): void
    {
        $kisi = KisiKisiSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->matematika->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'genap',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi-kisi lama',
            'tingkat_sekolah' => 'smp',
            'total_soal_target' => 5,
            'is_active' => true,
            'created_by' => $this->guru->id,
        ]);

        $item = KisiKisiItem::create([
            'kisi_kisi_soal_id' => $kisi->id,
            'tp_id' => $this->tpMatematika->id,
            'level_kognitif' => 'C1_mengingat',
            'jumlah_soal' => 5,
            'bobot_per_soal' => 4,
        ]);

        // Halaman edit kini memuat field Mapel/Tingkat/TA (sebelumnya tidak ada,
        // sehingga update selalu gagal validasi).
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/kisi-kisi-soal/{$kisi->id}/edit")
            ->assertOk()
            ->assertSee('subject-select')
            ->assertSee('Matematika')
            ->assertSee('Bentuk Soal')
            ->assertSee('Nomor Soal');

        $this->actingAs($this->guru)
            ->put("/{$this->guru->id}/kisi-kisi-soal/{$kisi->id}", [
                'subject_id' => $this->matematika->id,
                'grade_level_id' => $this->grade->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'genap',
                'jenis_ujian' => 'sts',
                'judul' => 'Kisi-kisi diperbarui',
                'total_soal_target' => 5,
                'items' => [[
                    'id' => $item->id,
                    'tp_id' => $this->tpMatematika->id,
                    'level_kognitif' => 'C6_mencipta',
                    'materi' => 'Statistika dasar',
                    'bentuk_soal' => 'uraian',
                    'nomor_soal' => '3',
                    'jumlah_soal' => 3,
                    'bobot_per_soal' => 5,
                ]],
            ])
            ->assertRedirect();

        $this->assertSame('Kisi-kisi diperbarui', $kisi->fresh()->judul);
        $this->assertSame('C6_mencita', $item->fresh()->level_kognitif);
        $this->assertSame(3, $item->fresh()->jumlah_soal);
        $this->assertSame('Statistika dasar', $item->fresh()->materi);
        $this->assertSame('uraian', $item->fresh()->bentuk_soal);
        $this->assertSame('3', $item->fresh()->nomor_soal);
    }

    private function makeUser(string $name, string $email, string $jabatan = 'Guru Umum'): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 3]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $this->seedSnapshot($user);

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

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => $jabatan,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
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
