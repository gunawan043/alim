<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Http\Middleware\RequirePermission;
use Tests\TestCase;

/**
 * Tests that just inspect middleware class structure.
 */
final class RequirePermissionMiddlewareTest extends TestCase
{
    public function test_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(RequirePermission::class));
    }

    public function test_middleware_has_handle_method(): void
    {
        $this->assertTrue(method_exists(RequirePermission::class, 'handle'));
    }

    public function test_middleware_takes_authorization_manager_via_constructor(): void
    {
        $reflection = new \ReflectionClass(RequirePermission::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertGreaterThanOrEqual(1, count($constructor->getParameters()));
    }
}
