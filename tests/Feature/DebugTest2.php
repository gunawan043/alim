<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest2 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);
        $dormB = Dormitory::factory()->create(['is_active' => true]);

        $this->actingAs($saUser);

        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id, $dormB->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        // Check what was created
        $records = DormitoryStaffAssignment::all(['id', 'user_id', 'dormitory_id', 'assigned_by_id', 'status']);
        foreach ($records as $r) {
            echo "\nRecord: user_id={$r->user_id} assigned_by={$r->assigned_by_id} dorm={substr($r->dormitory_id,0,8)} status={$r->status}";
        }

        echo "\n\nsaUser->id: {$saUser->id}\n";
        echo "asramaUser->id: {$asramaUser->id}\n";
        echo "dormA->id: {$dormA->id}\n";
        echo "dormB->id: {$dormB->id}\n";

        echo "\n\nAssertions:\n";
        echo 'assertDatabaseHas asramaUser+DormA: ';
        echo DormitoryStaffAssignment::where('user_id', $asramaUser->id)->where('dormitory_id', $dormA->id)->exists() ? 'YES' : 'NO';
        echo "\n";
        echo 'assertDatabaseHas saUser+DormA: ';
        echo DormitoryStaffAssignment::where('user_id', $saUser->id)->where('dormitory_id', $dormA->id)->exists() ? 'YES' : 'NO';
        echo "\n";

        $this->assertTrue(true);
    }
}
