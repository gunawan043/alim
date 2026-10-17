<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DebugTest11 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        Log::info('Starting test', ['sa' => 'x', 'asrama' => 'x']);

        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);
        $dormB = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;
        echo "\ndormA=".$dormA->id;
        echo "\ndormB=".$dormB->id;

        $this->actingAs($saUser);

        // Capture log entries
        Log::channel('test')->pushProcessor(function ($record) {
            echo "\n[LOG] ".$record['message'].' => '.json_encode($record['context']);

            return $record;
        });

        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $saUser->id,
            'targetUserId' => $asramaUser->id,
        ]), [
            'dormitory_ids' => [$dormA->id, $dormB->id],
            'start_date' => now()->toDateString(),
            'notes' => 'Test',
        ]);

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "\n[RECORD] user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
        }

        $this->assertTrue(true);
    }
}
