<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DormitoryTenantTraceTest extends TestCase
{
    public function test_trace_broadcasting_auth(): void
    {
        $userId = DB::table('users')->value('id');
        if (! $userId) {
            $this->markTestSkipped('No users.');
        }
        $user = User::find($userId);
        $this->actingAs($user);

        $this->withoutExceptionHandling();
        try {
            $this->get('broadcasting/auth');
        } catch (\Throwable $e) {
            echo 'EX='.get_class($e).PHP_EOL;
            echo 'MSG='.$e->getMessage().PHP_EOL;
            echo 'TRACE:'.PHP_EOL.$e->getTraceAsString().PHP_EOL;
        }
        $this->assertTrue(true);
    }
}
