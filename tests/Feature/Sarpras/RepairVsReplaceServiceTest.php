<?php

namespace Tests\Feature\Sarpras;

use App\Services\Sarpras\RepairVsReplaceService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class RepairVsReplaceServiceTest extends TestCase
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

    public function test_healthy_asset_returns_good(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id, 'condition' => 'baik']);

        $result = app(RepairVsReplaceService::class)->evaluate($asset);

        $this->assertContains($result['recommendation'], ['GOOD', 'MONITOR']);
    }

    public function test_damaged_old_asset_recommends_replace(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset([
            'school_id' => $school->id,
            'condition' => 'rusak_berat',
            'acquisition_date' => now()->subYears(8),
            'acquisition_price' => 100000,
        ]);

        $result = app(RepairVsReplaceService::class)->evaluate($asset);

        $this->assertContains($result['recommendation'], ['REPLACE', 'REPAIR']);
        $this->assertIsArray($result['rationale']);
    }

    public function test_recommendation_persists(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id, 'condition' => 'rusak_ringan']);

        app(RepairVsReplaceService::class)->evaluate($asset);

        $this->assertDatabaseHas('repair_vs_replace_evaluations', [
            'asset_id' => $asset->id,
            'is_current' => true,
        ]);
    }
}
