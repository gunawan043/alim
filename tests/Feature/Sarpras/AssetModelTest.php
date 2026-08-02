<?php

namespace Tests\Feature\Sarpras;

use App\Models\Asset;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetModelTest extends TestCase
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

        $this->artisan('cache:clear');
    }

    /** @test */
    public function uuid_is_generated_on_create()
    {
        $data = $this->assetData();
        $asset = Asset::create($data);
        $this->assertNotNull($asset->id);
        $this->assertNotFalse(filter_var($asset->id, FILTER_VALIDATE_UUID));
    }

    /** @test */
    public function uuid_does_not_change_on_update()
    {
        $data = $this->assetData();
        $asset = Asset::create($data);
        $originalId = $asset->id;
        $originalCode = $asset->asset_code;

        $asset->update(['asset_name' => 'Updated Name']);
        $this->assertEquals($originalId, $asset->fresh()->id);
        $this->assertEquals($originalCode, $asset->fresh()->asset_code);
    }

    /** @test */
    public function version_bump_increments_dashboard_version()
    {
        \Illuminate\Support\Facades\Cache::forever('sarpras_dashboard_version', 5);
        $asset = $this->createAsset();
        $asset->update(['condition' => 'rusak_ringan']);

        $this->assertGreaterThan(5, \Illuminate\Support\Facades\Cache::get('sarpras_dashboard_version'));
    }

    /** @test */
    public function condition_enum_accepts_valid_values()
    {
        $validConditions = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang', 'dihapus'];
        foreach ($validConditions as $cond) {
            $code = 'TEST-'.strtoupper(Str::random(5));
            Asset::firstOrCreate(['asset_code' => $code], [
                'school_id' => $this->createSchool()->id,
                'asset_name' => 'Test',
                'condition' => $cond,
                'status' => 'tersedia',
                'year' => date('Y'),
            ]);
        }
    }

    /** @test */
    public function status_enum_accepts_valid_values()
    {
        $data = $this->assetData();
        $asset = Asset::create($data);
        $asset->update(['status' => 'dipinjam']);
        $this->assertEquals('dipinjam', $asset->status);
    }

    /** @test */
    public function school_relationship_works()
    {
        $school = $this->createSchool();
        $data = $this->assetData(['school_id' => $school->id]);
        $asset = Asset::create($data);
        $this->assertNotNull($asset->fresh()->school);
    }

    /** @test */
    public function category_relationship_works()
    {
        $category = $this->createAssetCategory();
        $data = $this->assetData(['asset_category_id' => $category->id]);
        $asset = Asset::create($data);
        $this->assertEquals($category->id, $asset->category->id);
    }

    private function assetData(array $overrides = []): array
    {
        $school = $this->createSchool();

        return array_merge([
            'school_id' => $school->id,
            'asset_code' => 'TEST-'.strtoupper(Str::random(6)),
            'asset_name' => 'Test Asset '.strtoupper(Str::random(4)),
            'brand' => 'TestBrand',
            'model' => 'TestModel',
            'condition' => 'baik',
            'status' => 'tersedia',
            'acquisition_price' => 1000000,
            'year' => date('Y'),
            'room_id' => null,
        ], $overrides);
    }
}
