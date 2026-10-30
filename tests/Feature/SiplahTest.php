<?php

namespace Tests\Feature;

use App\Http\Controllers\SiplahController;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Support\PosProcurementConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiplahTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private WorkUnit $unit;

    private School $school;

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

        $this->unit = WorkUnit::create([
            'name' => 'Satuan Kerja SIPLah',
            'code' => 'SIPL-ALIM',
            'type' => 'Unit Administrasi',
            'is_active' => true,
        ]);

        $this->school = School::factory()->create([
            'name' => 'Sekolah SIPLah',
            'work_unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']);
        $this->user = User::factory()->create(['name' => 'Bendahara SIPLah', 'is_active' => true]);
        $this->user->assignRole($role);

        PosProcurementConfig::storeEnabled(true);
        PosProcurementConfig::storeBaseUrl('https://pos.test');
        PosProcurementConfig::storeToken('token-siplah');
        PosProcurementConfig::storeHeader('X-POS-PROCUREMENT-KEY');
    }

    private function request(string $method, string $uri, array $data = []): Request
    {
        $request = Request::create($uri, $method, $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->user);
        $this->actingAs($this->user);

        return $request;
    }

    public function test_proposal_disimpan_di_bump_tanpa_tabel_lokal_alim(): void
    {
        Http::fake([
            'https://pos.test/api/procurement/v1/siplah/proposals' => Http::response([
                'success' => true,
                'data' => [
                    'number' => 'SIPL-202610-0001', 'status' => 'draft', 'status_label' => 'Draft',
                    'items' => [], 'logs' => [], 'estimated_total' => 100000,
                ],
            ], 201),
        ]);

        $response = app(SiplahController::class)->store($this->request('POST', '/x', [
            'external_reference' => 'ALIM-SIPLAH-TEST',
            'work_unit_id' => $this->unit->id,
            'program_name' => 'Kebutuhan ATK',
            'purpose' => 'Kebutuhan kantor',
            'priority' => 'normal',
            'items' => [[
                'category' => 'ATK', 'name' => 'Kertas HVS', 'specification' => 'A4',
                'unit' => 'rim', 'qty' => 2, 'unit_price' => 50000,
            ]],
        ]), $this->user->id);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(Schema::hasTable('siplah_requests'));
        $this->assertFalse(Schema::hasTable('pos_procurement_requests'));

        Http::assertSent(fn ($http) => $http->method() === 'POST'
            && str_contains($http->url(), '/api/procurement/v1/siplah/proposals')
            && $http['work_unit_id'] === $this->unit->id
            && $http['program_name'] === 'Kebutuhan ATK');
    }

    public function test_detail_dan_linimasa_diambil_dari_bump(): void
    {
        Http::fake([
            'https://pos.test/api/procurement/v1/siplah/proposals/SIPL-202610-0001' => Http::response([
                'success' => true,
                'data' => [
                    'number' => 'SIPL-202610-0001', 'status' => 'submitted', 'status_label' => 'Diajukan',
                    'work_unit_id' => $this->unit->id,
                    'bump_status' => null, 'bump_status_label' => '—',
                    'program_name' => 'Kebutuhan ATK', 'estimated_total' => 100000,
                    'items' => [[
                        'name' => 'Kertas HVS', 'qty' => 2, 'unit' => 'rim',
                        'total' => 100000, 'qty_approved' => null, 'qty_fulfilled' => null, 'qty_backorder' => null,
                    ]],
                    'logs' => [[
                        'status' => 'submitted', 'status_label' => 'Diajukan', 'note' => 'Diajukan', 'at' => now()->toIso8601String(),
                    ]],
                ],
            ]),
        ]);

        $view = app(SiplahController::class)->show(
            $this->request('GET', '/x'),
            $this->user->id,
            'SIPL-202610-0001'
        );

        $this->assertSame('siplah.show', $view->name());
        $this->assertSame('Diajukan', $view->getData()['proposal']['status_label']);
        $this->assertCount(1, $view->getData()['proposal']['logs']);
    }

    public function test_aksi_approve_dikirim_ke_bump(): void
    {
        Http::fake([
            'https://pos.test/api/procurement/v1/siplah/proposals/SIPL-202610-0001/approve' => Http::response([
                'success' => true,
                'data' => ['number' => 'SIPL-202610-0001', 'status' => 'approved', 'status_label' => 'Disetujui'],
            ]),
        ]);

        $this->user->update(['is_system_admin' => true]);

        $response = app(SiplahController::class)->action(
            $this->request('POST', '/x', ['note' => 'OK']),
            $this->user->id,
            'SIPL-202610-0001',
            'approve'
        );

        $this->assertSame(302, $response->getStatusCode());
        Http::assertSent(fn ($http) => str_contains($http->url(), '/approve'));
    }

    public function test_token_pos_siplah_terenkripsi(): void
    {
        $raw = \DB::table('system_settings')->where('key', 'pos_procurement_api_key')->value('value');
        $this->assertStringStartsWith('enc:v1:', (string) $raw);
        $this->assertSame('token-siplah', PosProcurementConfig::token());
    }
}
