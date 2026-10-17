<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest16 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\n[CREATING] user_id=".$model->user_id.' auth='.auth()->id();

            return true;
        });

        DormitoryStaffAssignment::created(function ($model) {
            echo "\n[CREATED] user_id=".$model->user_id.' assigned_by='.$model->assigned_by_id;
        });

        // Direct model creation
        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\n=== Direct create ===";
        DormitoryStaffAssignment::create([
            'user_id' => $asramaUser->id,
            'dormitory_id' => $dormA->id,
            'assigned_by_id' => $saUser->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        echo "\n\n=== Via controller ===";
        $this->actingAs($saUser);
        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        echo "\n\nDone";
        $this->assertTrue(true);
    }
}
