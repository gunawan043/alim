<?php

namespace Tests\Feature;

use App\Http\Controllers\InstitutionDebtController;
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

class InstitutionDebtTest extends TestCase
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
            'name' => 'Satuan Hutang',
            'code' => 'DEBT-ALIM',
            'type' => 'Unit Administrasi',
            'is_active' => true,
        ]);

        $this->school = School::factory()->create([
            'name' => 'Sekolah Hutang',
            'work_unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']);
        $this->user = User::factory()->create(['name' => 'Bendahara Hutang', 'is_active' => true]);
        $this->user->assignRole($role);

        PosProcurementConfig::storeEnabled(true);
        PosProcurementConfig::storeBaseUrl('https://pos.test');
        PosProcurementConfig::storeToken('token-debt');
        PosProcurementConfig::storeHeader('X-POS-PROCUREMENT-KEY');
    }

    private function request(string $method, array $data = []): Request
    {
        $request = Request::create('/x', $method, $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->user);
        $this->actingAs($this->user);

        return $request;
    }

    public function test_halaman_hutang_mengambil_analisis_dari_bump(): void
    {
        Http::fake([
            'https://pos.test/api/procurement/v1/institution-debts/summary*' => Http::response([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_outstanding' => 125000,
                        'total_amount' => 175000,
                        'total_paid' => 50000,
                        'bump_outstanding' => 75000,
                        'siplah_outstanding' => 50000,
                        'open_items' => 2,
                        'overdue_amount' => 75000,
                        'overdue_items' => 1,
                        'due_soon_amount' => 50000,
                        'due_soon_items' => 1,
                        'units_count' => 1,
                        'average_outstanding' => 62500,
                        'collection_rate' => 28.6,
                    ],
                    'categories' => [
                        ['key' => 'bump', 'label' => 'BUMP', 'amount' => 100000, 'paid' => 25000, 'outstanding' => 75000, 'items' => 1],
                        ['key' => 'siplah', 'label' => 'SIPLah', 'amount' => 50000, 'paid' => 0, 'outstanding' => 50000, 'items' => 1],
                    ],
                    'monthly' => [],
                    'aging' => [],
                    'units' => [['id' => 1, 'name' => 'Satuan Hutang', 'code' => 'DEBT-ALIM', 'bump' => 75000, 'siplah' => 50000, 'total' => 125000, 'items' => 2]],
                    'statuses' => [],
                    'due_soon' => [],
                    'items' => [],
                    'generated_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        $view = app(InstitutionDebtController::class)->index($this->request('GET'), $this->user->id);

        $this->assertSame('institution-debts.index', $view->name());
        $this->assertSame(125000.0, (float) $view->getData()['debt']['summary']['total_outstanding']);
        $this->assertSame('DEBT-ALIM', $view->getData()['units']->first()->code);
        $this->assertStringContainsString('Hutang Lembaga', $view->render());

        Http::assertSent(fn ($http) => str_contains($http->url(), '/institution-debts/summary')
            && str_contains($http->url(), 'business_unit_codes=DEBT-ALIM')
            && str_contains($http->url(), 'months=6'));
    }

    public function test_koneksi_bump_gagal_tetap_menampilkan_halaman_kosong(): void
    {
        Http::fake([
            'https://pos.test/api/procurement/v1/institution-debts/summary*' => Http::response([
                'success' => false,
                'message' => 'Server POS mengalami kesalahan.',
            ], 500),
        ]);

        $view = app(InstitutionDebtController::class)->index($this->request('GET'), $this->user->id);

        $this->assertSame('institution-debts.index', $view->name());
        $this->assertSame(0.0, (float) $view->getData()['debt']['summary']['total_outstanding']);
        $this->assertNotNull($view->getData()['error']);
    }
}
