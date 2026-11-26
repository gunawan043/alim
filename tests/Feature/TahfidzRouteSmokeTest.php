<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_halaman_indeks_tahfidz_dapat_dibuka_super_admin(): void
    {
        $admin = User::create([
            'name' => 'Super Admin Smoke', 'email' => 'superadmin.smoke@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $paths = [
            'tahfidz/muqorror',
            'tahfidz/rpp',
            'tahfidz/halaqah',
            'tahfidz/setoran',
            'tahfidz/mutabaah',
            'tahfidz/tasmi',
            'tahfidz/monitoring',
            'tahfidz/input-hafalan',
            'tahfidz/audit',
            'tahfidz/consistency',
            'tahfidz/executive',
            'tahfidz/interventions',
            'tahfidz/buku-kerja',
            'tahfidz/progress',
            'tahfidz/targets',
            'tahfidz/attendance',
        ];

        foreach ($paths as $path) {
            $this->actingAs($admin)
                ->get("/{$admin->id}/{$path}")
                ->assertSuccessful();
        }
    }
}
