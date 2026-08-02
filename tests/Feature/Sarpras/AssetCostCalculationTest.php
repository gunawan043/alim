<?php

namespace Tests\Feature\Sarpras;

use App\Models\Asset;
use App\Models\AssetCostSnapshot;
use App\Models\MaintenanceHistory;
use App\Models\RepairCostHistory;
use App\Services\Sarpras\TcoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetCostCalculationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    /** @test */
    public function it_sums_purchase_repair_maintenance_sparepart_operational_into_total()
    {
        $asset = $this->makeAsset(['acquisition_price' => 10_000_000]);

        RepairCostHistory::create([
            'asset_id' => $asset->id,
            'cost_category' => 'labor',
            'description' => 'Labor',
            'amount' => 1_500_000,
            'incurred_date' => now()->toDateString(),
        ]);

        RepairCostHistory::create([
            'asset_id' => $asset->id,
            'cost_category' => 'sparepart',
            'description' => 'Sparepart',
            'amount' => 750_000,
            'incurred_date' => now()->toDateString(),
        ]);

        MaintenanceHistory::create([
            'asset_id' => $asset->id,
            'maintenance_type' => 'preventive',
            'performed_date' => now()->toDateString(),
            'cost' => 500_000,
        ]);

        DB::table('asset_maintenance_logs')->insert([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $this->makeWorkUnitId(),
            'asset_id' => $asset->id,
            'maintenance_date' => now()->toDateString(),
            'description' => 'Operational',
            'operational_cost' => 250_000,
            'status' => 'selesai',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tco = app(TcoService::class)->build($asset);

        $this->assertEquals(10_000_000, $tco['purchase_cost']);
        $this->assertEquals(2_250_000, $tco['repair_cost']);
        $this->assertEquals(500_000, $tco['maintenance_cost']);
        $this->assertEquals(750_000, $tco['sparepart_cost']);
        $this->assertEquals(250_000, $tco['operational_cost']);
        $this->assertEquals(13_750_000, $tco['total_cost']);
    }

    /** @test */
    public function it_persists_a_snapshot_and_returns_latest_for_asset()
    {
        $asset = $this->makeAsset(['acquisition_price' => 5_000_000]);

        RepairCostHistory::create([
            'asset_id' => $asset->id,
            'cost_category' => 'labor',
            'description' => 'Labor',
            'amount' => 1_000_000,
            'incurred_date' => now()->toDateString(),
        ]);

        $service = app(TcoService::class);
        $snap = $service->snapshot($asset);

        $this->assertInstanceOf(AssetCostSnapshot::class, $snap);
        $this->assertEquals(5_000_000, (float) $snap->purchase_cost);
        $this->assertEquals(1_000_000, (float) $snap->repair_cost);
        $this->assertEquals(6_000_000, (float) $snap->total_cost);

        $service->snapshot($asset);

        $this->assertEquals(1, AssetCostSnapshot::where('asset_id', $asset->id)->count());
    }

    /** @test */
    public function empty_asset_returns_zero_total()
    {
        $asset = $this->makeAsset(['acquisition_price' => null]);

        $tco = app(TcoService::class)->build($asset);

        $this->assertEquals(0, $tco['purchase_cost']);
        $this->assertEquals(0, $tco['total_cost']);
    }

    /** @test */
    public function it_summarizes_by_category()
    {
        $school = $this->makeSchool();
        $catA = $this->makeCategory('Furniture');
        $catB = $this->makeCategory('Electronics');

        $a1 = $this->makeAsset(['school_id' => $school->id, 'asset_category_id' => $catA->id, 'acquisition_price' => 1_000_000]);
        $a2 = $this->makeAsset(['school_id' => $school->id, 'asset_category_id' => $catA->id, 'acquisition_price' => 2_000_000]);
        $a3 = $this->makeAsset(['school_id' => $school->id, 'asset_category_id' => $catB->id, 'acquisition_price' => 5_000_000]);

        $service = app(TcoService::class);
        $service->snapshot($a1);
        $service->snapshot($a2);
        $service->snapshot($a3);

        $summary = $service->summarizeByCategory($school->id);

        $furniture = collect($summary)->firstWhere('category_name', 'Furniture');
        $electronics = collect($summary)->firstWhere('category_name', 'Electronics');

        $this->assertNotNull($furniture, 'Furniture summary should exist');
        $this->assertNotNull($electronics, 'Electronics summary should exist');
        $this->assertEquals(2, $furniture['asset_count']);
        $this->assertEquals(3_000_000, $furniture['total_cost']);
        $this->assertEquals(1, $electronics['asset_count']);
        $this->assertEquals(5_000_000, $electronics['total_cost']);
    }

    /** @test */
    public function it_returns_top_expensive_assets_ordered_by_total_cost()
    {
        $school = $this->makeSchool();
        $cheap = $this->makeAsset(['school_id' => $school->id, 'acquisition_price' => 500_000]);
        $rich = $this->makeAsset(['school_id' => $school->id, 'acquisition_price' => 50_000_000]);
        $mid = $this->makeAsset(['school_id' => $school->id, 'acquisition_price' => 10_000_000]);

        $service = app(TcoService::class);
        $service->snapshot($cheap);
        $service->snapshot($rich);
        $service->snapshot($mid);

        $top = $service->topExpensive($school->id, 3);

        $this->assertCount(3, $top);
        $this->assertEquals($rich->id, $top[0]['asset_id']);
        $this->assertEquals($mid->id, $top[1]['asset_id']);
        $this->assertEquals($cheap->id, $top[2]['asset_id']);
    }

    protected function makeAsset(array $overrides = []): Asset
    {
        $school = $this->makeSchool();

        return Asset::create(array_merge([
            'school_id' => $school->id,
            'work_unit_id' => $this->makeWorkUnitId(),
            'asset_code' => 'AST-'.strtoupper(Str::random(6)),
            'asset_name' => 'Asset '.strtoupper(Str::random(4)),
            'condition' => 'baik',
            'status' => 'tersedia',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeSchool(): \App\Models\School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'WU '.Str::random(4),
            'code' => 'WU-'.Str::random(4),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'school_code' => 'SC-'.Str::random(4),
            'npsn' => (string) rand(1000000000, 9999999999),
            'nss' => (string) rand(100000000000, 999999999999),
            'name' => 'School '.Str::random(4),
            'address' => 'Alamat',
            'is_active' => true,
        ]);

        return \App\Models\School::find($schoolId);
    }

    protected function makeCategory(string $name): \App\Models\AssetCategory
    {
        return \App\Models\AssetCategory::create([
            'name' => $name,
            'code' => 'C-'.Str::random(4),
            'is_active' => true,
        ]);
    }

    protected function makeWorkUnitId(): string
    {
        $id = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $id,
            'name' => 'WU-'.Str::random(4),
            'code' => 'WUC-'.Str::random(4),
        ]);

        return $id;
    }
}
