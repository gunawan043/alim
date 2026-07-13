<?php

namespace Tests\Traits;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesSarprasFixtures
{
    protected function createSchool(): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test',
            'code' => 'WU-' . Str::random(8),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'school_code' => 'SKS-' . Str::random(5),
            'npsn' => (string) (1000000000 + rand(0, 8999999999)),
            'nss' => (string) rand(100000000000, 999999999999),
            'name' => 'Sekolah Test ' . Str::random(3),
            'address' => 'Jl. Test No. 1',
            'is_active' => true,
        ]);

        return School::find($schoolId);
    }

    protected function createAssetCategory(): AssetCategory
    {
        return AssetCategory::create([
            'name' => 'Test Category ' . Str::random(4),
            'code' => 'TC-' . Str::random(4),
            'is_active' => true,
        ]);
    }

    protected function createAssetRoom(string $schoolId): AssetRoom
    {
        return AssetRoom::create([
            'school_id' => $schoolId,
            'work_unit_id' => null,
            'nama_ruang' => 'Test Room',
            'kode_ruang' => 'TR-' . Str::random(4),
            'is_active' => true,
        ]);
    }

    protected function createAsset(array $overrides = []): Asset
    {
        $school = $this->createSchool();
        $category = $this->createAssetCategory();

        return Asset::create(array_merge([
            'school_id' => $school->id,
            'asset_code' => 'AST-' . strtoupper(Str::random(6)),
            'asset_name' => 'Test Asset ' . strtoupper(Str::random(4)),
            'brand' => 'TestBrand',
            'model' => 'TestModel',
            'condition' => 'baik',
            'status' => 'tersedia',
            'acquisition_price' => 1000000,
            'year' => date('Y'),
            'asset_category_id' => $category->id,
            'is_active' => true,
        ], $overrides));
    }

    protected function createUser(string $role = 'Admin Sarpras'): User
    {
        return User::factory()->create(['role' => $role]);
    }
}