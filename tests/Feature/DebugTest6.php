<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest6 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;

        $this->actingAs($saUser);

        // Check the route URL
        $url = route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]);
        echo "\nRoute URL: ".$url;

        // Check route parameters
        $route = \Route::getRoutes()->getByName('user.asrama.staf-assignments.update');
        echo "\nRoute action: ".print_r($route->getAction(), true);
        echo "\nRoute parameters: ".print_r($route->parameterNames(), true);

        $response = $this->put($url, [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\nFinal: user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
        }

        $this->assertTrue(true);
    }
}
