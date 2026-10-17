<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryStaffAssignmentController;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest10 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\n[HOOK:creating] model->user_id=".$model->user_id.' auth_id='.auth()->id();
            echo "\n  [HOOK:creating] diff: ".($model->user_id === auth()->id() ? 'SAME - BUG!' : 'DIFFERENT - OK');
        });

        DormitoryStaffAssignment::created(function ($model) {
            echo "\n[HOOK:created] model->user_id=".$model->user_id.' assigned_by='.$model->assigned_by_id;
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);
        $dormB = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;

        $this->actingAs($saUser);

        // Call controller directly
        echo "\n\n=== CONTROLLER CALL TEST ===";
        $controller = new DormitoryStaffAssignmentController;

        // We need to test through HTTP
        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id, $dormB->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        echo "\n\nResponse status: ".$response->status();

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\nRecord: user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
            echo ($r->user_id === $asramaUser->id) ? ' [OK]' : ' [BUG - should be asramaUser]';
        }

        $this->assertTrue(true);
    }
}
