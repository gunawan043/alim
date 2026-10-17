<?php

namespace Tests\Feature;

use App\Http\Controllers\GlobalSearchController;
use App\Models\Kaldik;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pencarian global (menu + data) dan tipe/legenda baru Kaldik.
 */
class GlobalSearchAndKaldikTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

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

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Cari',
            'code' => 'UU-CARI',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '11001100',
            'name' => 'Sekolah Uji Cari',
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $this->user = User::create([
            'name' => 'Pencari Uji',
            'email' => 'pencari@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->user->assignRole($role);

        DB::table('permission_snapshots')->insert([
            'user_id' => $this->user->id,
            'scope_key' => 'test|'.$this->user->id,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $this->user->id),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);

        Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '1230009999',
            'name' => 'Ahmad Fulan Pencarian',
            'gender' => 'L',
        ]);
    }

    public function test_pencarian_global_menemukan_menu_dan_santri(): void
    {
        $request = Request::create('/search', 'GET', ['q' => 'santri']);
        $request->setUserResolver(fn () => $this->user);
        $request->attributes->set('schoolContextId', $this->school->id);

        $response = app(GlobalSearchController::class)->index($request, $this->user->id);
        $data = json_decode($response->getContent(), true);

        $labels = collect($data['menus'])->pluck('label')->all();
        $this->assertContains('Data Santri', $labels, 'Menu "Data Santri" ditemukan lewat kata kunci.');

        // Cari nama santri.
        $request2 = Request::create('/search', 'GET', ['q' => 'Ahmad Fulan']);
        $request2->setUserResolver(fn () => $this->user);
        $request2->attributes->set('schoolContextId', $this->school->id);

        $data2 = json_decode(app(GlobalSearchController::class)->index($request2, $this->user->id)->getContent(), true);

        $this->assertNotEmpty($data2['students']);
        $this->assertSame('Ahmad Fulan Pencarian', $data2['students'][0]['label']);
    }

    public function test_tipe_program_bersama_dan_warna_otomatis_kaldik(): void
    {
        $this->assertArrayHasKey('program_bersama', Kaldik::TYPE_OPTIONS);
        $this->assertSame('Program Bersama', Kaldik::TYPE_OPTIONS['program_bersama']);

        // Warna otomatis dari legenda default.
        $this->assertSame('#84CC16', kaldik_default_color('program_bersama'));
        $this->assertSame('#EF4444', kaldik_default_color('libur'));
        $this->assertSame('#64748B', kaldik_default_color('tidak_dikenal'));
    }
}
