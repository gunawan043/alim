<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest18 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;
        echo 'dormA='.$dormA->id;

        // Test 1: Direct URL, no route() helper
        $this->actingAs($saUser);
        $url = '/'.$saUser->id.'/asrama/staf-assignments/'.$asramaUser->id;
        echo "\n\nTest 1 - Direct URL: ".$url;

        DormitoryStaffAssignment::creating(function ($model) use ($asramaUser) {
            echo "\n  [CREATING] model_user_id=".$model->user_id.' expected='.$asramaUser->id.' same='.($model->user_id === $asramaUser->id ? 'YES' : 'NO');
        });

        $response = $this->put($url, [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        $records = DormitoryStaffAssignment::all(['user_id']);
        echo "\n  Result: user_id=".$records[0]->user_id.' expected='.$asramaUser->id.' match='.($records[0]->user_id === $asramaUser->id ? 'YES' : 'NO');

        // Test 2: Via route() helper
        $this->again();
        $url2 = route('user.asrama.staf-assignments.update', ['userId' => $saUser->id, 'targetUserId' => $asramaUser->id]);
        echo "\n\nTest 2 - route() helper: ".$url2;

        DormitoryStaffAssignment::creating(function ($model) use ($asramaUser) {
            echo "\n  [CREATING] model_user_id=".$model->user_id.' expected='.$asramaUser->id.' same='.($model->user_id === $asramaUser->id ? 'YES' : 'NO');
        });

        $response2 = $this->put($url2, [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        $records2 = DormitoryStaffAssignment::all(['user_id']);
        echo "\n  Result: user_id=".$records2[0]->user_id.' expected='.$asramaUser->id.' match='.($records2[0]->user_id === $asramaUser->id ? 'YES' : 'NO');

        $this->assertTrue(true);
    }
}
