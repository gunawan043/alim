<?php

namespace Tests\Feature\Sarpras;

use App\Services\Sarpras\RepairVsReplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class RepairVsReplaceServiceTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

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

        $this->assertContains($result['recommendation'], ['MONITOR', 'REPAIR', 'REPLACE', 'CRITICAL']);
        $this->assertIsArray($result['factors']);
    }

    public function test_recommendation_persists(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id, 'condition' => 'rusak_ringan']);

        app(RepairVsReplaceService::class)->persist($asset);

        $this->assertDatabaseHas('asset_recommendations', [
            'asset_id' => $asset->id,
        ]);
    }
}
