<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest9 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\n[HOOK:creating] model->user_id=".$model->user_id.' auth_id='.auth()->id();
            echo "\n  [HOOK:creating] model attributes: ".json_encode($model->getAttributes());
        });

        DormitoryStaffAssignment::created(function ($model) {
            echo "\n[HOOK:created] model->user_id=".$model->user_id.' assigned_by='.$model->assigned_by_id;
            echo "\n  [HOOK:created] model attributes: ".json_encode($model->getAttributes());
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;

        $this->actingAs($saUser);

        // Test direct creation
        echo "\n\n=== DIRECT CREATE TEST ===";
        $direct = DormitoryStaffAssignment::create([
            'user_id' => $asramaUser->id,
            'dormitory_id' => $dormA->id,
            'assigned_by_id' => $saUser->id,
            'start_date' => now(),
            'status' => 'active',
        ]);
        echo "\nDirect create result: user_id=".$direct->user_id.' assigned_by='.$direct->assigned_by_id;

        $this->assertTrue(true);
    }
}
