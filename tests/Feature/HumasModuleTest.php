<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HumasModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_humas_dapat_diakses_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin Humas Uji',
            'email' => 'admin.humas@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        foreach ([
            'humas/announcements',
            'humas/satisfaction',
            'humas/complaints',
            'humas/guest-visits',
            'humas/media-contacts',
            'gtk/massal',
        ] as $path) {
            $this->actingAs($admin)->get("/{$admin->id}/{$path}")->assertOk();
        }
    }
}
