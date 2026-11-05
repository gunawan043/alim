<?php

namespace Tests\Feature;

use App\Exports\GtkImportTemplateExport;
use App\Http\Controllers\GtkWizardController;
use App\Models\GtkEmployment;
use App\Models\GtkFamilyMember;
use App\Models\GtkProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Import GTK: pencocokan satuan kerja & jabatan otomatis, plus pembaruan
 * data lama tanpa menimpa data wajib.
 */
class GtkImportAutoMatchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $unitAId;

    private string $unitBId;

    private string $asramaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unitAId = $this->makeWorkUnit('MTs Contoh', 'MTS-A');
        $this->unitBId = $this->makeWorkUnit('MA Contoh', 'MA-B');
        $this->asramaId = $this->makeWorkUnit('Asrama SMP IT Putra Abu Hurairah', 'ASR-001');

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);

        $jenisGtkId = (string) Str::uuid();
        DB::table('jenis_gtk')->insert([
            'id' => $jenisGtkId,
            'nama' => 'Pendidik / Guru',
            'is_active' => 1,
            'urutan' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['Guru Umum', 'Guru Mata Pelajaran'] as $index => $name) {
            $positionId = (string) Str::uuid();

            DB::table('structural_positions')->insert([
                'id' => $positionId,
                'jenis_gtk_id' => $jenisGtkId,
                'role_id' => $guruRole->id,
                'code' => 'POS-'.$index,
                'name' => $name,
                'level' => 'sekolah',
                'hierarchy_level' => $index + 1,
                'urutan' => $index + 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->admin = User::create([
            'name' => 'Admin GTK',
            'email' => 'admin-gtk@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->admin->assignRole($adminRole);
        $this->actingAs($this->admin);
    }

    private function makeWorkUnit(string $name, string $code): string
    {
        $id = (string) Str::uuid();

        DB::table('work_units')->insert([
            'id' => $id,
            'name' => $name,
            'code' => $code,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function makePosition(string $name): string
    {
        $id = (string) Str::uuid();

        DB::table('structural_positions')->insert([
            'id' => $id,
            'jenis_gtk_id' => DB::table('jenis_gtk')->value('id'),
            'role_id' => Role::where('name', 'Guru')->value('id'),
            'code' => Str::slug($name),
            'name' => $name,
            'level' => 'sekolah',
            'hierarchy_level' => 5,
            'urutan' => 5,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function row(array $overrides = []): array
    {
        return array_merge([
            '_rowNum' => 2,
            'name' => 'Fulan bin Abu',
            'email' => 'fulan@test.local',
            'nik' => '3201234567890123',
            'nupy' => '2026001',
            'no_hp' => '081234567890',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
            'status_kepegawaian' => 'PTY',
        ], $overrides);
    }

    private function import(array $rows, ?string $workUnitId = null): array
    {
        $request = Request::create(
            '/'.$this->admin->id.'/gtk/import/store',
            'POST',
            ['work_unit_id' => $workUnitId, 'rows' => $rows]
        );
        $request->setUserResolver(fn () => $this->admin);

        $response = app(GtkWizardController::class)->importStore($request, $this->admin->id);

        return json_decode($response->getContent(), true);
    }

    public function test_template_export_memuat_kolom_satuan_kerja(): void
    {
        // DataSheet dideklarasikan di file GtkImportTemplateExport, jadi muat lewat kelas induknya.
        $sheet = (new GtkImportTemplateExport('Semua Satuan Kerja'))->sheets()[0];
        $columns = $sheet::COLUMNS;
        $rows = $sheet->collection();

        $this->assertContains('satuan_kerja', $rows[0]);
        $this->assertCount(count($columns), $rows[0]);
        $this->assertCount(count($columns), $sheet->columnWidths());
        $this->assertCount(count($columns), $rows[1], 'Baris contoh harus sepanjang header.');
    }

    public function test_template_tidak_memuat_pangkat_golongan_dan_nupy_pasangan(): void
    {
        $sheet = (new GtkImportTemplateExport('Semua Satuan Kerja'))->sheets()[0];
        $columns = array_keys($sheet::COLUMNS);

        $this->assertNotContains('pangkat_golongan', $columns);
        $this->assertNotContains('nupy_pasangan', $columns);

        // Urutan kelompok: informasi pribadi → kepegawaian → pendidikan → keluarga.
        $this->assertLessThan(array_search('nupy', $columns, true), array_search('name', $columns, true));
        $this->assertLessThan(array_search('jenjang_pendidikan', $columns, true), array_search('nupy', $columns, true));
        $this->assertLessThan(array_search('nama_pasangan', $columns, true), array_search('jenjang_pendidikan', $columns, true));
    }

    public function test_contoh_satuan_kerja_memakai_nama_unit_yang_ada(): void
    {
        $sheet = (new GtkImportTemplateExport('Semua Satuan Kerja'))->sheets()[0];
        $rows = $sheet->collection();
        $example = array_combine($rows[0], $rows[1]);

        $this->assertContains($example['satuan_kerja'], WorkUnit::all()->pluck('name')->all());
    }

    public function test_pasangan_sesama_gtk_tertaut_otomatis_tanpa_kolom_nupy_pasangan(): void
    {
        $this->import([$this->row(['satuan_kerja' => 'MTs Contoh'])]);

        $husband = GtkEmployment::where('nupy', '2026001')->firstOrFail();

        $result = $this->import([
            $this->row([
                '_rowNum' => 3,
                'name' => 'Fulanah binti Abu',
                'email' => 'fulanah@test.local',
                'nik' => '3201234567890124',
                'nupy' => '2026002',
                'no_hp' => '081234567891',
                'jenis_kelamin' => 'P',
                'nama_pasangan' => 'fulan bin abu',
                'satuan_kerja' => 'MTs Contoh',
            ]),
        ]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));

        $wife = GtkEmployment::where('nupy', '2026002')->firstOrFail();
        $member = GtkFamilyMember::where('gtk_profile_id', $wife->user->gtkProfile->id)
            ->where('relationship', 'suami')
            ->first();

        $this->assertNotNull($member, 'Pasangan harus tercatat sebagai anggota keluarga.');
        $this->assertSame($husband->user->gtkProfile->id, $member->linked_gtk_profile_id);
        $this->assertSame('2026001', $member->spouse_nupy);
    }

    public function test_satuan_kerja_dicocokkan_dari_kolom_excel_tanpa_memilih_unit(): void
    {
        $result = $this->import([$this->row(['satuan_kerja' => 'MA Contoh'])]);

        $this->assertSame([], $result['failed'], json_encode($result['failed']));
        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['updated']);

        $employment = GtkEmployment::where('nupy', '2026001')->first();
        $this->assertNotNull($employment);
        // Kolom teks memakai nama tampil WorkUnit (accessor ucwords), sama seperti di layar.
        $this->assertSame(WorkUnit::find($this->unitBId)->name, $employment->satuan_kerja);

        $this->assertDatabaseHas('gtk_work_unit', [
            'user_id' => $employment->user_id,
            'work_unit_id' => $this->unitBId,
            'is_primary' => 1,
        ]);
    }

    public function test_satuan_kerja_dicocokkan_lewat_kode_unit(): void
    {
        $result = $this->import([$this->row(['satuan_kerja' => 'mts-a'])]);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('gtk_work_unit', [
            'work_unit_id' => $this->unitAId,
            'is_primary' => 1,
        ]);
    }

    public function test_pilihan_satuan_kerja_manual_dipakai_bila_kolom_kosong(): void
    {
        $result = $this->import([$this->row()], $this->unitAId);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('gtk_work_unit', [
            'work_unit_id' => $this->unitAId,
            'is_primary' => 1,
        ]);
    }

    public function test_satuan_kerja_tak_dikenal_dilaporkan_dengan_nama_nilainya(): void
    {
        $result = $this->import([$this->row(['satuan_kerja' => 'Unit Antah Berantah'])]);

        $this->assertSame(0, $result['imported']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('Unit Antah Berantah', $result['failed'][0]['reason']);
        $this->assertStringContainsString('Referensi: Satuan Kerja', $result['failed'][0]['reason']);
    }

    public function test_satuan_kerja_kosong_dan_tanpa_pilihan_manual_ditolak(): void
    {
        $result = $this->import([$this->row()]);

        $this->assertSame(0, $result['imported']);
        $this->assertStringContainsString('Satuan kerja kosong', $result['failed'][0]['reason']);
    }

    public function test_nama_satuan_kerja_yang_lebih_panjang_tetap_cocok_otomatis(): void
    {
        $result = $this->import([$this->row(['satuan_kerja' => 'MTs Contoh Kota'])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));
        $this->assertDatabaseHas('gtk_work_unit', [
            'work_unit_id' => $this->unitAId,
            'is_primary' => 1,
        ]);
    }

    public function test_pengasuhan_dibaca_sebagai_asrama(): void
    {
        $result = $this->import([$this->row(['satuan_kerja' => 'Pengasuhan SMP Putra'])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));
        $this->assertDatabaseHas('gtk_work_unit', [
            'work_unit_id' => $this->asramaId,
            'is_primary' => 1,
        ]);
    }

    public function test_pengasuhan_tanpa_tingkat_memakai_kandidat_teratas(): void
    {
        $result = $this->import([$this->row(['satuan_kerja' => 'Pengasuhan'])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));
        $this->assertDatabaseHas('gtk_work_unit', [
            'work_unit_id' => $this->asramaId,
            'is_primary' => 1,
        ]);
    }

    public function test_email_dibuat_otomatis_bila_kosong(): void
    {
        $result = $this->import([$this->row([
            'email' => '',
            'nik' => '',
            'no_hp' => '',
            'satuan_kerja' => 'MTs Contoh',
        ])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));

        $user = User::where('email', '2026001@gtk.local')->first();
        $this->assertNotNull($user, 'Email internal harus dibuat dari NUPY.');
        $this->assertSame('Fulan bin Abu', $user->name);
        $this->assertNull(GtkProfile::where('user_id', $user->id)->value('nik'));
        $this->assertDatabaseMissing('gtk_contacts', ['user_id' => $user->id]);
    }

    public function test_jabatan_kosong_tetap_diimport(): void
    {
        $result = $this->import([$this->row([
            'jabatan' => '',
            'satuan_kerja' => 'MTs Contoh',
        ])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));

        $employment = GtkEmployment::where('nupy', '2026001')->firstOrFail();
        $this->assertNull($employment->jabatan_id);
    }

    public function test_satuan_kerja_ambigu_memakai_kandidat_teratas(): void
    {
        // 'Contoh' sama-sama mendekati MTs Contoh dan MA Contoh: tetap diimport
        // dengan kandidat teratas, dan pengguna bisa mengubahnya di preview.
        $result = $this->import([$this->row(['satuan_kerja' => 'Contoh'])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));

        $employment = GtkEmployment::where('nupy', '2026001')->firstOrFail();
        $placedUnit = DB::table('gtk_work_unit')->where('user_id', $employment->user_id)->value('work_unit_id');

        $this->assertContains($placedUnit, [$this->unitAId, $this->unitBId]);
    }

    public function test_konfirmasi_manual_mengalahkan_teks_ambigu(): void
    {
        $result = $this->import([
            $this->row([
                'satuan_kerja' => 'Contoh',
                'work_unit_id' => $this->unitBId,
            ]),
        ]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));
        $this->assertDatabaseHas('gtk_work_unit', [
            'work_unit_id' => $this->unitBId,
            'is_primary' => 1,
        ]);
    }

    public function test_jabatan_ambigu_memakai_kandidat_teratas(): void
    {
        $result = $this->import([
            $this->row(['satuan_kerja' => 'MTs Contoh', 'jabatan' => 'Guru']),
        ]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));

        $employment = GtkEmployment::where('nupy', '2026001')->firstOrFail();
        $this->assertNotNull($employment->jabatan_id, 'Jabatan ambigu tetap diisi dari kandidat teratas.');
    }

    public function test_jabatan_mengikuti_satuan_kerja(): void
    {
        $this->makeWorkUnit('Perpustakaan', 'DEP-PERPUS');
        $this->makePosition('Kepala Asrama');
        $perpusPositionId = $this->makePosition('Kepala Perpustakaan');

        // GTK pertama menetapkan bahwa unit Perpustakaan memakai Kepala Perpustakaan.
        $this->import([$this->row([
            'nupy' => '2026009',
            'email' => 'pustakawan@test.local',
            'nik' => '3201234567890109',
            'satuan_kerja' => 'Perpustakaan',
            'jabatan' => 'Kepala Perpustakaan',
        ])]);

        // Baris berikutnya hanya menulis "KEPALA": konteks unit harus menang.
        $result = $this->import([$this->row([
            'nupy' => '2026010',
            'email' => 'pustakawan2@test.local',
            'nik' => '3201234567890110',
            'satuan_kerja' => 'Perpustakaan',
            'jabatan' => 'KEPALA',
        ])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));

        $employment = GtkEmployment::where('nupy', '2026010')->firstOrFail();
        $this->assertSame($perpusPositionId, $employment->jabatan_id);
    }

    public function test_email_sudah_terdaftar_diperbarui_bukan_digagalkan(): void
    {
        $this->import([$this->row(['satuan_kerja' => 'MTs Contoh'])]);

        $result = $this->import([$this->row([
            'nupy' => '2026999',
            'email' => 'fulan@test.local',
            'satuan_kerja' => 'MTs Contoh',
        ])]);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['updated'], json_encode($result['failed']));
        $this->assertSame(1, User::where('email', 'fulan@test.local')->count());
        $this->assertSame(0, GtkEmployment::where('nupy', '2026999')->count());
    }

    public function test_jenis_gtk_tak_dikenal_tetap_diimport(): void
    {
        $result = $this->import([$this->row([
            'jenis_gtk' => '',
            'jabatan' => 'Jabatan Antah Berantah',
            'satuan_kerja' => 'MTs Contoh',
        ])]);

        $this->assertSame(1, $result['imported'], json_encode($result['failed']));

        $employment = GtkEmployment::where('nupy', '2026001')->firstOrFail();
        $this->assertNull($employment->jenis_gtk_id);
    }

    public function test_resolve_endpoint_mengembalikan_status_pencocokan(): void
    {
        $request = Request::create(
            '/'.$this->admin->id.'/gtk/import/resolve',
            'POST',
            [
                'satuan_kerja' => ['MTs Contoh', 'Contoh', 'Unit Antah Berantah'],
                'jabatan' => ['Guru Umum', 'Guru'],
            ]
        );
        $request->setUserResolver(fn () => $this->admin);

        $response = app(GtkWizardController::class)->resolveImportValues($request, $this->admin->id);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame('auto', $payload['satuan_kerja']['MTs Contoh']['status']);
        $this->assertSame($this->unitAId, $payload['satuan_kerja']['MTs Contoh']['match']['id']);

        $this->assertSame('ambiguous', $payload['satuan_kerja']['Contoh']['status']);
        $this->assertNull($payload['satuan_kerja']['Contoh']['match']);
        $this->assertNotEmpty($payload['satuan_kerja']['Contoh']['candidates']);

        $this->assertContains(
            $payload['satuan_kerja']['Unit Antah Berantah']['status'],
            ['unknown', 'ambiguous']
        );

        $this->assertSame('auto', $payload['jabatan']['Guru Umum']['status']);
        $this->assertSame('ambiguous', $payload['jabatan']['Guru']['status']);
    }

    public function test_import_ulang_memperbarui_data_tanpa_menimpa_data_wajib(): void
    {
        $this->import([$this->row(['satuan_kerja' => 'MTs Contoh'])], $this->unitAId);

        $employment = GtkEmployment::where('nupy', '2026001')->firstOrFail();
        $userId = $employment->user_id;

        $result = $this->import([
            $this->row([
                'name' => 'Nama Diubah',
                'email' => 'email.baru@test.local',
                'nik' => '3201234567899999',
                'no_hp' => '089999999999',
                'jabatan' => 'Guru Mata Pelajaran',
                'status_kepegawaian' => 'GTT',
                'satuan_kerja' => 'MA Contoh',
            ]),
        ]);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['updated']);

        $user = User::find($userId);
        $this->assertSame('Fulan bin Abu', $user->name);
        $this->assertSame('fulan@test.local', $user->email);

        $employment->refresh();
        $this->assertSame('3201234567890123', $employment->user->gtkProfile->nik);
        $this->assertSame('081234567890', $employment->user->contact->no_hp);

        // Kolom non-wajib tetap diperbarui.
        $this->assertSame('Guru Mata Pelajaran', $employment->jabatan);
        $this->assertSame('GTT', $employment->status_kepegawaian);
        $this->assertSame(WorkUnit::find($this->unitBId)->name, $employment->satuan_kerja);

        $this->assertDatabaseHas('gtk_work_unit', [
            'user_id' => $userId,
            'work_unit_id' => $this->unitBId,
            'is_primary' => 1,
        ]);

        // Import ulang tidak menumpuk baris penempatan untuk unit yang sama,
        // dan hanya satu penempatan yang ditandai utama.
        $this->assertSame(1, DB::table('gtk_work_unit')->where('user_id', $userId)->where('work_unit_id', $this->unitBId)->count());
        $this->assertSame(1, DB::table('gtk_work_unit')->where('user_id', $userId)->where('is_primary', 1)->count());
    }
}
