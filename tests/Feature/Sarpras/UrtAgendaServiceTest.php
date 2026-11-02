<?php

namespace Tests\Feature\Sarpras;

use App\Models\AssetMaintenanceSchedule;
use App\Models\ProcurementRequest;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Sarpras\UrtAgendaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class UrtAgendaServiceTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_agenda_berisi_jadwal_mendatang_dan_prioritas_terlambat(): void
    {
        $asset = $this->createAsset();
        $owner = User::factory()->create();
        URL::defaults(['userId' => $owner->id]);

        AssetMaintenanceSchedule::create([
            'work_unit_id' => $asset->work_unit_id,
            'school_id' => $asset->school_id,
            'asset_id' => $asset->id,
            'maintenance_type' => 'Servis AC',
            'frequency' => 'bulanan',
            'next_maintenance_date' => today()->addDays(3),
            'responsible_user_id' => $owner->id,
            'is_active' => true,
            'created_by' => $owner->id,
        ]);

        AssetMaintenanceSchedule::create([
            'work_unit_id' => $asset->work_unit_id,
            'school_id' => $asset->school_id,
            'asset_id' => $asset->id,
            'maintenance_type' => 'Cek instalasi listrik',
            'frequency' => 'bulanan',
            'next_maintenance_date' => today()->subDays(4),
            'responsible_user_id' => $owner->id,
            'is_active' => true,
            'created_by' => $owner->id,
        ]);

        $agenda = app(UrtAgendaService::class)->build($asset->school_id);

        $this->assertNotEmpty($agenda['agenda']);
        $this->assertSame('Servis AC', $agenda['agenda'][0]['title']);
        $this->assertSame('Pemeliharaan', $agenda['agenda'][0]['category']);
        $this->assertSame(1, $agenda['counts']['maintenance_overdue']);

        $labels = array_column($agenda['priorities'], 'label');
        $this->assertContains('Pemeliharaan terlambat', $labels);
    }

    public function test_prioritas_kosong_saat_tidak_ada_tindak_lanjut(): void
    {
        $asset = $this->createAsset();

        $agenda = app(UrtAgendaService::class)->build($asset->school_id);

        $this->assertSame([], $agenda['agenda']);
        $this->assertSame([], $agenda['priorities']);
        $this->assertSame(0, $agenda['counts']['maintenance_overdue']);
    }

    public function test_pengadaan_dengan_tanggal_kirim_masuk_agenda(): void
    {
        $asset = $this->createAsset();
        $owner = User::factory()->create();
        URL::defaults(['userId' => $owner->id]);

        ProcurementRequest::create([
            'work_unit_id' => $asset->work_unit_id,
            'school_id' => $asset->school_id,
            'request_number' => 'PR-'.now()->format('Ymd').'-001',
            'request_date' => today(),
            'purpose' => 'Pengadaan meja kelas',
            'status' => 'approved',
            'delivery_date' => today()->addDays(10),
            'requested_by' => $owner->id,
        ]);

        $agenda = app(UrtAgendaService::class)->build($asset->school_id);

        $categories = array_column($agenda['agenda'], 'category');
        $this->assertContains('Pengadaan', $categories);
    }

    public function test_dashboard_urt_menampilkan_agenda_dan_prioritas(): void
    {
        $user = $this->makeUrtUser();
        $asset = $this->createAsset(['school_id' => $user->employment->school_id]);

        AssetMaintenanceSchedule::create([
            'work_unit_id' => $asset->work_unit_id,
            'school_id' => $asset->school_id,
            'asset_id' => $asset->id,
            'maintenance_type' => 'Servis genset',
            'frequency' => 'bulanan',
            'next_maintenance_date' => today()->addDays(2),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('user.dashboard.unit-rumah-tangga', ['userId' => $user->id]));

        $response->assertOk();
        $response->assertSee('Agenda Mendatang', false);
        $response->assertSee('Servis genset', false);
    }

    private function makeUrtUser(): User
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit URT', 'code' => 'URT'.random_int(1000, 9999),
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '7'.random_int(1000000, 9999999),
            'name' => 'Sekolah URT',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Petugas URT',
            'email' => 'urt-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => 'Unit Rumah Tangga', 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }
}
