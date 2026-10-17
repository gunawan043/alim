<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryStaffAssignmentController;
use App\Http\Requests\Dormitory\UpdateStaffScopeRequest;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DebugTest12 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        echo "\nsaUser=".$saUser->id;
        echo "\nasramaUser=".$asramaUser->id;

        // Create assignment directly via model
        DormitoryStaffAssignment::create([
            'user_id' => $asramaUser->id,
            'dormitory_id' => $dormA->id,
            'assigned_by_id' => $saUser->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        echo "\nDirect create: ";
        $direct = DormitoryStaffAssignment::first();
        echo "user_id={$direct->user_id} assigned_by={$direct->assigned_by_id}";
        echo ($direct->user_id === $asramaUser->id) ? ' [OK]' : ' [BUG]';
        echo "\n";

        $this->actingAs($saUser);

        echo "\n=== Controller call ===\n";

        // Intercept the controller method
        $controller = new DormitoryStaffAssignmentController;

        // Call update via controller directly to bypass HTTP
        $request = Request::create(
            route('user.asrama.staf-assignments.update', ['userId' => $saUser->id, 'targetUserId' => $asramaUser->id]),
            'PUT',
            [
                'dormitory_ids' => [$dormA->id],
                'start_date' => now()->toDateString(),
                'notes' => 'Test',
            ]
        );
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $saUser);

        // Bind route parameters
        $route = \Route::getRoutes()->getByName('user.asrama.staf-assignments.update');
        $request->setRouteResolver(fn () => $route);
        $route->bind($request);
        $request->attributes->set('route', $route);

        try {
            $response = $controller->update(UpdateStaffScopeRequest::createFrom($request), $asramaUser->id);
            echo 'Controller returned status: '.$response->status()."\n";
        } catch (\Throwable $e) {
            echo 'Controller error: '.$e->getMessage()."\n";
        }

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo "Record: user_id={$r->user_id} assigned_by={$r->assigned_by_id}";
            echo ($r->user_id === $asramaUser->id) ? ' [OK]' : ' [BUG]';
            echo "\n";
        }

        $this->assertTrue(true);
    }
}
