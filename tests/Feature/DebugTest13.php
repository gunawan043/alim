<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryStaffAssignmentController;
use App\Http\Requests\Dormitory\UpdateStaffScopeRequest;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest13 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\nCREATING - model->user_id=".$model->user_id.' auth='.auth()->id().' diff='.($model->user_id !== auth()->id() ? 'OK' : 'BUG');
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        $this->actingAs($saUser);

        // Direct controller call bypassing HTTP
        $controller = new DormitoryStaffAssignmentController;
        $request = new UpdateStaffScopeRequest;
        $request->merge([
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        $response = $controller->update($request, $asramaUser->id);

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\nFinal: user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
            echo ($r->user_id === $asramaUser->id) ? ' [OK]' : ' [BUG]';
        }

        $this->assertTrue(true);
    }
}
