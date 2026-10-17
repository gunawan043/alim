<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest19 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        // Hook into the create to capture the exact values
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\n[HOOK] user_id=".$model->user_id.' type='.gettype($model->user_id);
        });

        DormitoryStaffAssignment::created(function ($model) {
            echo "\n[HOOK CREATED] user_id=".$model->user_id.' assigned_by='.$model->assigned_by_id;
        });

        // Also hook into the model's boot to see what happens
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\n[MODEL HOOK] user_id=".$model->user_id.' is_empty='.(empty($model->user_id) ? 'YES' : 'NO');
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;

        $this->actingAs($saUser);

        // Create via HTTP
        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        echo "\n\nResponse: ".$response->status();

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\nDB record: user_id=".$r->user_id.' assigned_by='.$r->assigned_by_id;
        }

        $this->assertTrue(true);
    }
}
