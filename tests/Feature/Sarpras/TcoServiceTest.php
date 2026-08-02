<?php

namespace Tests\Feature\Sarpras;

use App\Models\RepairCostHistory;
use App\Services\Sarpras\TcoService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class TcoServiceTest extends TestCase
{
    use CreatesSarprasFixtures;

    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$migrated = true;
        }
    }

    public function test_snapshot_contains_all_components(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id, 'acquisition_price' => 5000000]);

        RepairCostHistory::create([
            'asset_id' => $asset->id,
            'school_id' => $school->id,
            'amount' => 150000,
            'cost_category' => 'repair',
            'incurred_date' => now(),
        ]);

        $snapshot = app(TcoService::class)->snapshot($asset);

        $this->assertEquals($asset->id, $snapshot->asset_id);
        $this->assertGreaterThanOrEqual(5000000, $snapshot->acquisition_cost_total);
        $this->assertEquals(150000, $snapshot->repair_cost_total);
        $this->assertGreaterThan(0, $snapshot->tco_total);
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
            'incurred_date' => now(),
        ]);

        $breakdown = app(TcoService::class)->breakdown($asset);

        $this->assertArrayHasKey('repair', $breakdown);
        $this->assertArrayHasKey('maintenance', $breakdown);
    }
}
