<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest8 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\n[HOOK:creating] model->user_id=".$model->user_id.' auth_id='.auth()->id();
        });

        DormitoryStaffAssignment::created(function ($model) {
            echo "\n[HOOK:created] model->user_id=".$model->user_id.' assigned_by='.$model->assigned_by_id;
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;
        echo "\ndormA=".$dormA->id;

        $this->actingAs($saUser);

        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        echo "\n[RESPONSE] status=".$response->status();

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\n[RECORD] user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
        }

        $this->assertTrue(true);
    }
}
