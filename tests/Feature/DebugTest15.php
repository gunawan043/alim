<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest15 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\n[HOOK] CREATING user_id=".$model->user_id.' auth_id='.auth()->id();
        });

        DormitoryStaffAssignment::created(function ($model) {
            echo "\n[HOOK] CREATED user_id=".$model->user_id.' assigned_by='.$model->assigned_by_id;
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        $this->actingAs($saUser);

        echo "\n=== BEFORE REQUEST ===";
        echo "\nsaUser: ".$saUser->id;
        echo "\nasramaUser: ".$asramaUser->id;
        echo "\ndormA: ".$dormA->id;

        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        echo "\n\n=== AFTER REQUEST ===";
        echo "\nResponse status: ".$response->status();
        echo "\nResponse location: ".($response->headers->get('Location') ?? 'none');

        // Check session for flash data
        echo "\nSession success: ".session('success', 'NONE');

        // Check database
        $count = DormitoryStaffAssignment::count();
        echo "\nTotal assignments in DB: ".$count;

        $records = DormitoryStaffAssignment::all(['id', 'user_id', 'assigned_by_id', 'status']);
        foreach ($records as $r) {
            echo "\nRecord id={$r->id}: user_id=".$r->user_id.' assigned_by='.$r->assigned_by_id.' status='.$r->status;
        }

        // Also check for trashed
        $trashed = DormitoryStaffAssignment::withTrashed()->count();
        echo "\nTotal (including trashed): ".$trashed;

        $this->assertTrue(true);
    }
}
