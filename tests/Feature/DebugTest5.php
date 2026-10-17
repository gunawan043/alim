<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest5 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\nCREATING - user_id=".$model->user_id.' auth='.auth()->id().' target='.($model->user_id === auth()->id() ? 'SAME!' : 'DIFFERENT');
        });

        DormitoryStaffAssignment::created(function ($model) {
            echo "\nCREATED - user_id=".$model->user_id.' assigned_by='.$model->assigned_by_id;
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=$saUser->id";
        echo "\nasramaUser=$asramaUser->id";
        echo "\ndormA=$dormA->id";

        $this->actingAs($saUser);

        echo "\n\n=== Calling PUT ===";

        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        echo "\n\nResponse status: ".$response->status();

        $records = DormitoryStaffAssignment::all(['id', 'user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\nFinal record: user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
        }

        $this->assertTrue(true);
    }
}
