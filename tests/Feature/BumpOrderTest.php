<?php

namespace Tests\Feature;

use App\Http\Controllers\BumpOrderController;
use App\Models\BumpOrderCache;
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

class BumpOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private WorkUnit $unit;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($t) {
                $t->uuid('id')->primary();
                $t->string('name')->nullable();
                $t->timestamps();
            });
        }

        $this->unit = WorkUnit::create(['name' => 'Unit BUMP', 'code' => 'BUMP-ALIM', 'type' => 'Unit Administrasi', 'is_active' => true]);
        $this->school = School::factory()->create(['name' => 'Sekolah BUMP', 'work_unit_id' => $this->unit->id, 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']);
        $this->user = User::factory()->create(['name' => 'Bendahara BUMP', 'is_active' => true]);
        $this->user->assignRole($role);

        PosProcurementConfig::storeEnabled(true);
        PosProcurementConfig::storeBaseUrl('https://pos.test');
        PosProcurementConfig::storeToken('token-bump');
        PosProcurementConfig::storeHeader('X-POS-PROCUREMENT-KEY');
    }

    private function req(string $method, array $data = []): Request
    {
        $r = Request::create('/x', $method, $data);
        $r->attributes->set('schoolContextId', $this->school->id);
        $r->setUserResolver(fn () => $this->user);
        $this->actingAs($this->user);

        return $r;
    }

    public function test_order_bump_dikirim_dan_disimpan_sebagai_cache(): void
    {
        Http::fake([
            'https://pos.test/api/procurement/v1/bump-orders' => Http::response([
                'success' => true,
                'data' => [
                    'number' => 'BUMP-20261029-ABC', 'status' => 'pending', 'payment_status' => 'unpaid',
                    'payment_method' => 'cash', 'total' => 50000, 'items' => [], 'logs' => [],
                ],
            ], 201),
        ]);

        $response = app(BumpOrderController::class)->store($this->req('POST', [
            'work_unit_id' => $this->unit->id,
            'payment_method' => 'cash',
            'items' => [['product_id' => 1, 'qty' => 2]],
        ]), $this->user->id);

        $this->assertSame(302, $response->getStatusCode());
        $cache = BumpOrderCache::firstOrFail();
        $this->assertSame('BUMP-20261029-ABC', $cache->pos_order_number);
        $this->assertSame('pending', $cache->status);
        $this->assertSame('unpaid', $cache->payment_status);
        Http::assertSent(fn ($http) => str_contains($http->url(), '/bump-orders') && $http['payment_method'] === 'cash');
    }

    public function test_detail_sinkron_dari_bump(): void
    {
        $cache = BumpOrderCache::create([
            'user_id' => $this->user->id, 'work_unit_id' => $this->unit->id, 'school_id' => $this->school->id,
            'external_reference' => 'ALIM-BUMP-1', 'pos_order_number' => 'BUMP-20261029-XYZ',
            'payment_method' => 'cash', 'status' => 'pending', 'payment_status' => 'unpaid', 'total' => 50000,
            'payload' => [], 'last_synced_at' => now(),
        ]);

        Http::fake([
            'https://pos.test/api/procurement/v1/bump-orders/BUMP-20261029-XYZ' => Http::response([
                'success' => true,
                'data' => ['number' => 'BUMP-20261029-XYZ', 'status' => 'completed', 'payment_status' => 'paid', 'total' => 50000, 'items' => [], 'logs' => []],
            ]),
        ]);

        $view = app(BumpOrderController::class)->show($this->req('GET'), $this->user->id, 'BUMP-20261029-XYZ');
        $this->assertSame('bump-orders.show', $view->name());
        $this->assertSame('completed', $cache->fresh()->status);
        $this->assertSame('paid', $cache->fresh()->payment_status);
    }

    public function test_cancel_order_melepas_reservasi_di_bump(): void
    {
        $cache = BumpOrderCache::create([
            'user_id' => $this->user->id, 'work_unit_id' => $this->unit->id, 'school_id' => $this->school->id,
            'external_reference' => 'ALIM-BUMP-2', 'pos_order_number' => 'BUMP-20261029-CAN',
            'payment_method' => 'cash', 'status' => 'pending', 'payment_status' => 'unpaid', 'total' => 10000,
            'payload' => [], 'last_synced_at' => now(),
        ]);

        Http::fake([
            'https://pos.test/api/procurement/v1/bump-orders/BUMP-20261029-CAN/cancel' => Http::response([
                'success' => true,
                'data' => ['number' => 'BUMP-20261029-CAN', 'status' => 'cancelled', 'payment_status' => 'unpaid', 'total' => 10000, 'items' => [], 'logs' => []],
            ]),
        ]);

        app(BumpOrderController::class)->cancel($this->req('POST', ['reason' => 'Batal']), $this->user->id, 'BUMP-20261029-CAN');
        $this->assertSame('cancelled', $cache->fresh()->status);
    }
}
