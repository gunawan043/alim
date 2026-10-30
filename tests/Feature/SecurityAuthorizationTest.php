<?php

namespace Tests\Feature;

use App\Http\Middleware\BindOrganizationContext;
use App\Http\Middleware\BlockDangerousUploads;
use App\Http\Middleware\DenyStudentOperatorAi;
use App\Http\Middleware\EnsureDormitoryStudentOperatorAccess;
use App\Http\Middleware\EnsureEmployeeAccess;
use App\Http\Middleware\EnsureRoleAccess;
use App\Http\Middleware\EnsureStudentPasswordChanged;
use App\Http\Middleware\RestrictAdminIp;
use App\Http\Middleware\SchoolContextMiddleware;
use App\Http\Middleware\TrustHosts;
use App\Models\GtkEducation;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\JenisGtk;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Regresi hardening otorisasi tingkat aplikasi:
 *  - area Super Admin tidak bisa diakses user biasa,
 *  - penulisan lintas-pegawai pada data GTK diblokir (anti-IDOR),
 *  - self-service tidak bisa mengubah kepegawaian/status aktif,
 *  - mass-assignment pada riwayat pendidikan dicegah.
 */
class SecurityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Middleware konteks/role yang tidak relevan untuk menguji guard spesifik.
     *
     * @return array<int, class-string>
     */
    private function routeGuards(): array
    {
        return [
            EnsureEmployeeAccess::class,
            EnsureRoleAccess::class,
            SchoolContextMiddleware::class,
            BindOrganizationContext::class,
            RestrictAdminIp::class,
            EnsureStudentPasswordChanged::class,
            DenyStudentOperatorAi::class,
            EnsureDormitoryStudentOperatorAccess::class,
        ];
    }

    public function test_super_admin_area_is_forbidden_for_regular_users(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($user)
            ->get("/{$user->id}/sa/users")
            ->assertForbidden();
    }

    public function test_api_security_headers_are_enforced(): void
    {
        config(['services.pos.api_key' => 'security-test-key']);

        $response = $this->getJson('/api/pos/v1/ping', [
            'X-ALIM-POS-KEY' => 'security-test-key',
        ]);

        $response->assertOk();

        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_gtk_update_is_forbidden_for_other_employees(): void
    {
        $alice = $this->employee('Alice');
        $bob = $this->employee('Bob');

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($alice)
            ->putJson("/{$alice->id}/gtk/{$bob->id}", [])
            ->assertForbidden();
    }

    public function test_gtk_profile_is_accessible_for_self(): void
    {
        $alice = $this->employee('Alice');

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($alice)
            ->get("/{$alice->id}/gtk/{$alice->id}")
            ->assertOk();
    }

    public function test_gtk_education_cross_user_write_is_forbidden(): void
    {
        $alice = $this->employee('Alice');
        $bob = $this->employee('Bob');

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($alice)
            ->postJson("/{$alice->id}/gtk/educations", [
                'user_id' => $bob->id,
                'jenjang_pendidikan' => 'S1',
                'nama_satuan_pendidikan' => 'Universitas Test',
                'tahun_lulus' => 2020,
                'status' => 'LULUS',
            ])
            ->assertForbidden();

        $this->assertSame(0, GtkEducation::where('user_id', $bob->id)->count());
    }

    public function test_gtk_education_update_cannot_reassign_owner(): void
    {
        $alice = $this->employee('Alice');
        $bob = $this->employee('Bob');

        $education = GtkEducation::create([
            'user_id' => $alice->id,
            'jenjang_pendidikan' => 'S1',
            'nama_satuan_pendidikan' => 'Universitas Test',
            'tahun_lulus' => 2020,
            'status' => 'LULUS',
        ]);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($alice)
            ->putJson("/{$alice->id}/gtk/educations/{$education->id}", [
                'user_id' => $bob->id,
                'jenjang_pendidikan' => 'S2',
            ])
            ->assertOk();

        $education->refresh();

        $this->assertSame($alice->id, $education->user_id, 'Kepemilikan data tidak boleh dipindah.');
        $this->assertSame('S2', $education->jenjang_pendidikan);
    }

    public function test_self_gtk_update_cannot_change_employment_or_active_status(): void
    {
        $this->seedRegion();

        $jenisGtk = JenisGtk::create([
            'nama' => 'Pendidik / Guru',
            'deskripsi' => 'Guru',
            'is_active' => true,
        ]);

        $positionA = $this->position('Guru A');
        $positionB = $this->position('Kepala Sekolah B');

        $workUnitA = WorkUnit::factory()->create(['name' => 'Unit A', 'is_active' => true]);
        $workUnitB = WorkUnit::factory()->create(['name' => 'Unit B', 'is_active' => true]);

        $alice = User::factory()->create([
            'name' => 'Alice',
            'is_active' => true,
        ]);

        GtkProfile::create([
            'user_id' => $alice->id,
            'nik' => '3273010101900001',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ]);

        $employment = GtkEmployment::create([
            'user_id' => $alice->id,
            'status_kepegawaian' => 'GTY',
            'nupy' => '20010220251036',
            'jenis_gtk' => 'Pendidik / Guru',
            'jenis_gtk_id' => $jenisGtk->id,
            'jabatan' => 'Guru A',
            'jabatan_id' => $positionA->id,
        ]);

        $workUnitPivot = GtkWorkUnit::create([
            'user_id' => $alice->id,
            'work_unit_id' => $workUnitA->id,
            'jabatan' => 'Guru A',
            'is_primary' => true,
        ]);

        $response = $this->withoutMiddleware($this->routeGuards())
            ->actingAs($alice)
            ->putJson("/{$alice->id}/gtk/{$alice->id}", [
                'name' => 'Alice Updated',
                'email' => $alice->email,
                'nik' => '3273010101900001',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
                'kontak' => [
                    'no_hp' => '081234567890',
                    'no_whatsapp' => '081234567890',
                ],
                'kepegawaian' => [
                    'nupy' => '20010220251036',
                    'jenis_gtk' => $jenisGtk->id,
                    'jabatan' => $positionB->id,
                    'status_kepegawaian' => 'GTY',
                    'tmt' => '2020-01-01',
                    'nomor_sk' => 'SK-001',
                    'tanggal_sk' => '2020-01-01',
                ],
                'work_unit_id' => $workUnitB->id,
                'alamat_domisili' => [
                    'province_code' => '32',
                    'city_code' => '3273',
                    'district_code' => '3273010',
                    'village_code' => '3273010001',
                    'jalan' => 'Jl. Test No. 1',
                    'rt_rw' => '001/001',
                    'kode_pos' => '40123',
                ],
                'is_active' => '0',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSame('Alice Updated', $alice->fresh()->name);
        $this->assertTrue((bool) $alice->fresh()->is_active, 'Status aktif tidak boleh diubah sendiri.');
        $this->assertSame($positionA->id, $employment->fresh()->jabatan_id, 'Jabatan tidak boleh diubah sendiri.');
        $this->assertSame($workUnitA->id, $workUnitPivot->fresh()->work_unit_id, 'Satuan kerja tidak boleh diubah sendiri.');
    }

    public function test_trusted_host_allowlist_rejects_unknown_hosts(): void
    {
        $middleware = new TrustHosts($this->app);

        $patterns = array_values(array_filter($middleware->hosts()));
        $this->assertNotEmpty($patterns);

        $matches = function (string $host) use ($patterns): bool {
            foreach ($patterns as $pattern) {
                if (preg_match('/'.$pattern.'/i', $host) === 1) {
                    return true;
                }
            }

            return false;
        };

        $this->assertTrue($matches('localhost'), 'localhost harus diizinkan untuk pengembangan.');
        $this->assertFalse($matches('evil.example.com'), 'Host asing harus ditolak.');
    }

    public function test_dangerous_upload_extension_is_blocked(): void
    {
        $request = Request::create('/upload', 'POST', [], [], [
            'file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ]);

        $this->expectException(HttpException::class);

        (new BlockDangerousUploads)->handle($request, fn () => response('ok'));
    }

    public function test_safe_upload_extension_is_allowed(): void
    {
        $request = Request::create('/upload', 'POST', [], [], [
            'file' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ]);

        $response = (new BlockDangerousUploads)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    private function employee(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]);

        GtkProfile::create([
            'user_id' => $user->id,
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ]);

        return $user;
    }

    private function position(string $name): StructuralPosition
    {
        $position = StructuralPosition::create([
            'code' => Str::slug($name).'-'.Str::random(4),
            'name' => $name,
            'level' => 'unit',
            'hierarchy_level' => 1,
            'urutan' => 1,
            'is_active' => true,
        ]);

        // Tabel `positions` adalah warisan skema yang masih dipakai aturan validasi.
        DB::table('positions')->insert([
            'id' => $position->id,
            'name' => $position->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $position;
    }

    private function seedRegion(): void
    {
        DB::table('indonesia_provinces')->insert(['code' => '32', 'name' => 'Jawa Barat']);
        DB::table('indonesia_cities')->insert(['code' => '3273', 'province_code' => '32', 'name' => 'Kota Bandung']);
        DB::table('indonesia_districts')->insert(['code' => '3273010', 'city_code' => '3273', 'name' => 'Sukajadi']);
        DB::table('indonesia_villages')->insert(['code' => '3273010001', 'district_code' => '3273010', 'name' => 'Sukajadi']);
    }
}
