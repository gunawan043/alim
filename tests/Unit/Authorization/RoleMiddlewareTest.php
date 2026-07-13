<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use ReflectionClass;
use Tests\TestCase;

/**
 * Structural verification that RoleMiddleware includes the Spatie hasRole fallback.
 *
 * This test verifies the fallback path exists without needing a database.
 */
final class RoleMiddlewareTest extends TestCase
{
    public function test_middleware_includes_has_role_fallback(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../app/Http/Middleware/RoleMiddleware.php');

        // The source must contain the hasRole fallback call
        $this->assertStringContainsString(
            '$user->hasRole',
            $source,
            'RoleMiddleware must include a hasRole() fallback for identity-only role names'
        );

        // Should first try canPermission before hasRole
        $canPos = strpos($source, 'canPermission');
        $hasRolePos = strpos($source, 'hasRole');

        $this->assertNotFalse($canPos, 'RoleMiddleware should call canPermission() for snapshot-based checks');
        $this->assertGreaterThan(-1, $hasRolePos);
        $this->assertLessThan($hasRolePos, $canPos,
            'RoleMiddleware must try canPermission() before falling back to hasRole()'
        );
    }

    public function test_middleware_class_is_loadable(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\RoleMiddleware::class));

        $ref = new ReflectionClass(\App\Http\Middleware\RoleMiddleware::class);
        $this->assertTrue($ref->hasMethod('handle'));

        $method = $ref->getMethod('handle');
        $params = $method->getParameters();
        // handle(Request $request, Closure $next, ...$roles)
        $this->assertEquals('request', $params[0]->getName());
        $this->assertEquals('next', $params[1]->getName());
        $this->assertTrue($params[2]->isVariadic(), 'Third param should be variadic (...$roles)');
    }
}
