<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugStafTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspect_final_response()
    {
        app()['cache']->forget('spatie.permission.cache');
        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $user = User::factory()->create(['is_system_admin' => false, 'is_active' => true]);
        $asramaRole = Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']);
        $user->assignRole($asramaRole);

        $dormA = Dormitory::factory()->create(['is_active' => true]);

        DormitoryStaffAssignment::create([
            'user_id' => $user->id,
            'dormitory_id' => $dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('root'));
        if ($response->isRedirect()) {
            $response2 = $this->get($response->headers->get('Location'));
            $content = $response2->getContent();

            echo "\n=== FINAL RESPONSE INSPECTION ===\n";
            echo 'Status: '.$response2->getStatusCode()."\n";
            echo 'Content length: '.strlen($content)."\n";
            echo "\nFirst 1000 chars:\n";
            echo substr($content, 0, 1000)."\n";
            echo "\nLast 500 chars:\n";
            echo substr($content, -500)."\n";

            // Check if it's an error page
            if (strpos($content, 'Whoops') !== false || strpos($content, 'exception') !== false) {
                echo "\n*** ERROR PAGE DETECTED ***\n";
            }

            // Search for any content
            if (preg_match('/<title>([^<]+)<\/title>/', $content, $m)) {
                echo 'Page title: '.$m[1]."\n";
            }

            echo "=== END ===\n";
        }

        $this->assertTrue(true);
    }
}
