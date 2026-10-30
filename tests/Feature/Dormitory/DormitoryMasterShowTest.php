<?php

namespace Tests\Feature\Dormitory;

use App\Models\Dormitory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DormitoryMasterShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_dormitory_detail_page_renders_for_admin(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $dormitory = Dormitory::factory()->create([
            'is_active' => true,
            'head_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('user.dormitory-master.show', [
                'userId' => $admin->id,
                'asramaUuid' => $dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Hapus Asrama');
    }
}
