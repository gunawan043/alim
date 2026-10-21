<?php

namespace Tests\Feature;

use App\Models\GtkFamilyMember;
use App\Models\GtkProfile;
use App\Models\User;
use App\Services\GtkFamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtkFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_spouses_share_one_household_and_children_are_not_duplicated(): void
    {
        $aUser = User::create([
            'name' => 'Guru Suami',
            'email' => 'suami.family@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $bUser = User::create([
            'name' => 'Guru Istri',
            'email' => 'istri.family@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $a = GtkProfile::create(['user_id' => $aUser->id, 'jenis_kelamin' => 'L']);
        $b = GtkProfile::create(['user_id' => $bUser->id, 'jenis_kelamin' => 'P']);

        $service = app(GtkFamilyService::class);
        $service->sync($a, [
            [
                'relationship' => 'istri',
                'nama' => $bUser->name,
                'jenis_kelamin' => 'P',
                'gtk_id' => $bUser->id,
            ],
            [
                'relationship' => 'anak',
                'nama' => 'Anak Bersama',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2015-01-01',
            ],
        ]);
        $service->sync($b, [[
            'relationship' => 'anak',
            'nama' => 'Anak Bersama',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2015-01-01',
        ]]);

        $a->refresh();
        $b->refresh();

        $this->assertSame($a->household_id, $b->household_id);
        $this->assertSame($b->id, $a->spouse_gtk_profile_id);
        $this->assertSame($a->id, $b->spouse_gtk_profile_id);
        $this->assertSame(1, GtkFamilyMember::where('household_id', $a->household_id)
            ->where('relationship', 'anak')->count());
    }
}
