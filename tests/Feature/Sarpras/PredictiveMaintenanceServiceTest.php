<?php

namespace Tests\Feature\Sarpras;

use App\Services\Sarpras\PredictiveMaintenanceService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class PredictiveMaintenanceServiceTest extends TestCase
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

    public function test_predict_for_asset_returns_required_keys(): void
    {
        $school = $this->createSchool();
        $asset = $this->createAsset(['school_id' => $school->id]);

        $prediction = app(PredictiveMaintenanceService::class)->predictForAsset($asset);

        $this->assertArrayHasKey('asset_id', $prediction);
        $this->assertArrayHasKey('risk_level', $prediction);
        $this->assertArrayHasKey('mtbf_days', $prediction);
        $this->assertArrayHasKey('mttr_days', $prediction);
        $this->assertContains($prediction['risk_level'], ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);
    }

    public function test_high_risk_assets_returns_array(): void
    {
        $school = $this->createSchool();
        $this->createAsset(['school_id' => $school->id]);

        $high = app(PredictiveMaintenanceService::class)->highRiskAssets($school->id, 5);

        $this->assertIsArray($high);
    }
}
