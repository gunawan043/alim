<?php

namespace Tests\Feature\Sarpras;

use App\Models\RepairCostHistory;
use App\Services\Sarpras\TcoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class TcoServiceTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    public function test_snapshot_contains_all_components(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id, 'acquisition_price' => 5000000]);

        RepairCostHistory::create([
            'asset_id' => $asset->id,
            'school_id' => $school->id,
            'amount' => 150000,
            'cost_category' => 'repair',
            'description' => 'Perbaikan fixture',
            'incurred_date' => now(),
        ]);

        $snapshot = app(TcoService::class)->snapshot($asset);

        $this->assertEquals($asset->id, $snapshot->asset_id);
        $this->assertGreaterThanOrEqual(5000000, (float) $snapshot->purchase_cost);
        $this->assertEquals(150000, (float) $snapshot->repair_cost);
        $this->assertGreaterThan(0, (float) $snapshot->total_cost);
    }

    public function test_breakdown_returns_categorized_history(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id]);

        RepairCostHistory::create([
            'asset_id' => $asset->id,
            'school_id' => $school->id,
            'amount' => 100000,
            'cost_category' => 'repair',
            'description' => 'Perbaikan fixture',
            'incurred_date' => now(),
        ]);

        $breakdown = app(TcoService::class)->build($asset);

        $this->assertArrayHasKey('repair_cost', $breakdown);
        $this->assertArrayHasKey('maintenance_cost', $breakdown);
        $this->assertArrayHasKey('breakdown', $breakdown);
        $this->assertEquals(100000, $breakdown['repair_cost']);
    }
}
