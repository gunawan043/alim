<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Authorization\ValueObjects\OrganizationContext;
use Tests\TestCase;

/**
 * Unit tests for Authorization gate integration.
 */
final class AuthorizationGateTest extends TestCase
{
    public function test_authorization_manager_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Authorization\Services\AuthorizationManager::class));
    }

    public function test_authorization_manager_can_method_exists(): void
    {
        $managerClass = \App\Authorization\Services\AuthorizationManager::class;

        $this->assertTrue(method_exists($managerClass, 'allows'));
    }

    public function test_gate_facade_is_registered(): void
    {
        $gate = $this->app->make(\Illuminate\Contracts\Auth\Access\Gate::class);

        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Access\Gate::class, $gate);
    }

    public function test_organization_context_properties(): void
    {
        $context = new OrganizationContext(
            schoolId: 'school-a',
            academicYearId: 'ay-2025',
            roleDimension: 'default',
        );

        $this->assertSame('school-a', $context->schoolId);
        $this->assertSame('ay-2025', $context->academicYearId);
        $this->assertSame('default', $context->roleDimension);
    }

    public function test_organization_context_to_scope_key_deterministic(): void
    {
        $ctx1 = new OrganizationContext('school-a', 'ay-2025', 'default');
        $ctx2 = new OrganizationContext('school-a', 'ay-2025', 'default');

        $scope1 = $ctx1->toScopeKey();
        $scope2 = $ctx2->toScopeKey();

        $this->assertEquals((string) $scope1, (string) $scope2);
    }

    public function test_organization_context_different_params_produce_different_keys(): void
    {
        $ctx1 = new OrganizationContext('school-a', 'ay-2025', 'default');
        $ctx2 = new OrganizationContext('school-b', 'ay-2025', 'default');

        $this->assertNotEquals((string) $ctx1->toScopeKey(), (string) $ctx2->toScopeKey());
    }
}
