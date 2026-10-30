<?php

namespace Tests\Feature\Sarpras;

use App\Services\Sarpras\SarprasIntelligenceDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class SarprasIntelligenceDashboardTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    public function test_build_returns_full_dashboard(): void
    {
        $school = $this->createSchool();
        $this->createAsset(['school_id' => $school->id]);

        $data = app(SarprasIntelligenceDashboard::class)->build($school->id);

        $this->assertArrayHasKey('overview', $data);
        $this->assertArrayHasKey('tco', $data);
        $this->assertArrayHasKey('rvr', $data);
        $this->assertArrayHasKey('predictive', $data);
        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('spend_trend', $data);
        $this->assertArrayHasKey('generated_at', $data);
    }

    public function test_rvr_distribution_has_all_categories(): void
    {
        $school = $this->createSchool();
        $this->createAsset(['school_id' => $school->id]);

        $data = app(SarprasIntelligenceDashboard::class)->build($school->id);

        $this->assertArrayHasKey('GOOD', $data['rvr']);
        $this->assertArrayHasKey('MONITOR', $data['rvr']);
        $this->assertArrayHasKey('REPAIR', $data['rvr']);
        $this->assertArrayHasKey('REPLACE', $data['rvr']);
    }
}
