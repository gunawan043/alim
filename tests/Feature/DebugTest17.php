<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryStaffAssignmentController;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DebugTest17 extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $saUser = User::factory()->create(['is_system_admin' => true]);
        $asramaUser = User::factory()->create(['is_system_admin' => false]);
        $dormA = Dormitory::factory()->create(['is_active' => true]);

        $this->actingAs($saUser);

        // Check the route
        $route = Route::getRoutes()->getByName('user.asrama.staf-assignments.update');
        echo "\nRoute compiled expression: ".$route->getCompiled()->getExpression();
        echo "\nRoute parameter names: ".implode(', ', $route->parameterNames())."\n";

        // Get route parameters from a test request
        $testRequest = Request::create(
            '/'.$saUser->id.'/asrama/staf-assignments/'.$asramaUser->id,
            'PUT'
        );
        $route->bind($testRequest);

        echo 'Route targetUserId from request: '.$testRequest->route('targetUserId')."\n";
        echo 'Route userId from request: '.$testRequest->route('userId')."\n";

        // Now test with the actual HTTP call but capture parameters
        $controller = new DormitoryStaffAssignmentController;

        // Create a proper request with route binding
        $request = Request::create(
            route('user.asrama.staf-assignments.update', [
                'userId' => $saUser->id,
                'targetUserId' => $asramaUser->id,
            ]),
            'PUT',
            [
                'dormitory_ids' => [$dormA->id],
                'start_date' => now()->toDateString(),
                'notes' => 'Test',
            ]
        );
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $saUser);
        $request->setRouteResolver(fn () => $route);
        $route->bind($request);

        echo 'Request route targetUserId: '.$request->route('targetUserId')."\n";
        echo 'Request route userId: '.$request->route('userId')."\n";

        // Call controller method directly with the bound request
        try {
            $response = $controller->update($request, $request->route('targetUserId'));
            echo 'Controller response status: '.$response->status()."\n";
        } catch (\Exception $e) {
            echo 'Controller error: '.$e->getMessage()."\n";
        }

        $records = DormitoryStaffAssignment::all(['user_id', 'assigned_by_id']);
        foreach ($records as $r) {
            echo 'Record: user_id='.$r->user_id.' assigned_by='.$r->assigned_by_id."\n";
        }

        $this->assertTrue(true);
    }
}
