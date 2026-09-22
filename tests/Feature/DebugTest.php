<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\n=== Before action ===\n";
        echo 'saUser->id: '.$saUser->id."\n";
        echo 'asramaUser->id: '.$asramaUser->id."\n";
        echo 'dormA->id: '.$dormA->id."\n";

        $this->actingAs($saUser);

        echo "\n=== After actingAs ===\n";
        echo 'auth()->id(): '.auth()->id()."\n";
        echo 'saUser->id (after): '.$saUser->id."\n";
        echo 'asramaUser->id (after): '.$asramaUser->id."\n";

        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        echo "\n=== After request ===\n";
        echo 'Response status: '.$response->status()."\n";

        $records = DormitoryStaffAssignment::where('dormitory_id', $dormA->id)->get(['user_id']);
        foreach ($records as $r) {
            echo 'Created record user_id: '.$r->user_id."\n";
        }

        $this->assertTrue(true);
    }
}
