<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest7 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        DormitoryStaffAssignment::creating(function ($model) {
            echo "\nCREATING - model->user_id=".$model->user_id.' auth()->id='.auth()->id();
        });

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;

        $this->actingAs($saUser);

        // Make request directly without route() helper
        $url = '/'.$saUser->id.'/asrama/staf-assignments/'.$asramaUser->id;
        echo "\nDirect URL: ".$url;

        $response = $this->put($url, [
            'dormitory_ids' => [$dormA->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\nFinal: user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
        }

        $this->assertTrue(true);
    }
}
