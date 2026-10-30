<?php

namespace Tests\Feature\Sarpras;

use App\Models\RepairCostHistory;
use App\Services\Sarpras\AssetPassportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetPassportV2Test extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    public function test_build_full_includes_legacy_fields(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id]);

        $passport = app(AssetPassportService::class)->buildFull($asset);

        $this->assertArrayHasKey('identity', $passport);
        $this->assertArrayHasKey('health', $passport);
        $this->assertArrayHasKey('warranty', $passport);
        $this->assertArrayHasKey('financial', $passport);
    }

    public function test_build_passport_v2_includes_intelligence(): void
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

        $passport = app(AssetPassportService::class)->buildPassportV2($asset);

        $this->assertArrayHasKey('identity', $passport);
        $this->assertArrayHasKey('tco', $passport);
        $this->assertArrayHasKey('repair_vs_replace', $passport);
        $this->assertArrayHasKey('predictive', $passport);
        $this->assertArrayHasKey('criticality', $passport);
    }

    public function test_criticality_levels(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id, 'condition' => 'rusak_berat']);

        $passport = app(AssetPassportService::class)->buildPassportV2($asset);

        $this->assertContains(strtoupper($passport['criticality']['risk_level']), ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);
    }
}
