<?php

namespace Tests\Traits;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesSarprasFixtures
{
    /** Pengguna bersama untuk kolom `created_by` (dibuat sekali per test). */
    protected ?User $sarprasFixtureUser = null;

    protected function createSchool(): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test',
            'code' => 'WU-'.Str::random(8),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'school_code' => 'SKS-'.Str::random(5),
            'npsn' => (string) (1000000000 + rand(0, 8999999999)),
            'nss' => (string) rand(100000000000, 999999999999),
            'name' => 'Sekolah Test '.Str::random(3),
            'address' => 'Jl. Test No. 1',
            'is_active' => true,
        ]);

        return School::find($schoolId);
    }

    protected function createAssetCategory(): AssetCategory
    {
        return AssetCategory::create([
            'name' => 'Test Category '.Str::random(4),
            'code' => 'TC-'.Str::random(4),
            'asset_type' => 'tidak_bergerak',
            'is_active' => true,
        ]);
    }

    protected function createAssetRoom(string $schoolId): AssetRoom
    {
        return AssetRoom::create([
            'school_id' => $schoolId,
            'work_unit_id' => null,
            'room_name' => 'Test Room',
            'room_code' => 'TR-'.Str::random(4),
            'room_type' => 'lainnya',
            'is_active' => true,
        ]);
    }

    protected function createAsset(array $overrides = []): Asset
    {
        $school = $this->createSchool();
        $category = $this->createAssetCategory();

        return Asset::create(array_merge([
            'work_unit_id' => $school->work_unit_id,
            'school_id' => $school->id,
            'asset_code' => 'AST-'.strtoupper(Str::random(6)),
            'asset_name' => 'Test Asset '.strtoupper(Str::random(4)),
            'brand' => 'TestBrand',
            'model' => 'TestModel',
            'condition' => 'baik',
            'status' => 'tersedia',
            'acquisition_price' => 1000000,
            'acquisition_source' => 'hibah',
            'asset_category_id' => $category->id,
            'created_by' => $this->fixtureUser()->id,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Pengguna netral (tanpa role istimewa) yang dipakai sebagai `created_by`
     * pada fixture aset. Dibuat sekali per test agar tidak membengkakkan query.
     */
    protected function fixtureUser(): User
    {
        $this->sarprasFixtureUser ??= $this->createUser('');

        return $this->sarprasFixtureUser;
    }

    protected function createUser(string $role = 'Unit Rumah Tangga'): User
    {
        $user = User::factory()->create();
        if ($role !== '') {
            $roleModel = Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
            $user->assignRole($roleModel);
        }

        return $user;
    }
}
