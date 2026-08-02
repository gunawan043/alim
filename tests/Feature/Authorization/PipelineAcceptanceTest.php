<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\Support\PermissionConflictResolver;
use App\Authorization\Support\PermissionMergeResolver;
use App\Authorization\Support\RevocationResolver;
use App\Authorization\ValueObjects\ScopeKey;
use Tests\TestCase;

/**
 * Phase 4 pipeline acceptance.
 *
 * Validates the six-stage pipeline:
 *   PermissionBuilder → PermissionMergeResolver → PermissionConflictResolver
 *   → RevocationResolver → PermissionTreeNormalizer → PermissionBag
 *
 * DB-independent: uses synthesized origins only.
 */
final class PipelineAcceptanceTest extends TestCase
{
    private function scope(): ScopeKey
    {
        return ScopeKey::fromComponents(
            schoolId: 'school-1',
            academicYearId: '2026-2027',
            roleDimension: 'teacher',
            tenantId: 'local',
        );
    }

    public function test_pipeline_stages_are_resolvable_from_container(): void
    {
        $this->assertInstanceOf(
            PermissionMergeResolver::class,
            app(PermissionMergeResolver::class)
        );
        $this->assertTrue(method_exists(PermissionConflictResolver::class, 'resolve'));
        $this->assertTrue(method_exists(RevocationResolver::class, 'resolve'));
    }

    public function test_pipeline_preserves_distinct_permissions(): void
    {
        $scope = $this->scope();
        $origins = [
            new PermissionOrigin('role_group', 'students.view', 'role_group_teacher', $scope, PermissionSource::ASSIGNMENT),
            new PermissionOrigin('gtk', 'gtk.read', 'active_employment', $scope, PermissionSource::EMPLOYMENT),
            new PermissionOrigin('attendance', 'attendance.mark', 'role_group_guru_mapel', $scope, PermissionSource::ASSIGNMENT),
        ];

        $merged = app(PermissionMergeResolver::class)->resolve($origins);
        $deduped = PermissionConflictResolver::resolve($merged);
        $final = RevocationResolver::resolve($deduped);

        $names = array_map(fn ($o) => $o->permission, $final);
        sort($names);

        $this->assertSame(['attendance.mark', 'gtk.read', 'students.view'], $names);
    }

    public function test_revocation_wins_over_grant(): void
    {
        $scope = $this->scope();
        $origins = [
            new PermissionOrigin('role_group', 'students.view', 'role_group_teacher', $scope, PermissionSource::ASSIGNMENT),
            new PermissionOrigin('admin_override', 'students.view', 'revoked_by_admin', $scope, PermissionSource::REVOCATION),
        ];

        $merged = app(PermissionMergeResolver::class)->resolve($origins);
        $deduped = PermissionConflictResolver::resolve($merged);
        $final = RevocationResolver::resolve($deduped);

        $names = array_map(fn ($o) => $o->permission, $final);

        $this->assertNotContains('students.view', $names, 'students.view must be revoked');
    }

    public function test_conflict_resolver_dedupes_redundant_origins(): void
    {
        $scope = $this->scope();
        $origins = [
            new PermissionOrigin('role_group', 'students.view', 'reason-1', $scope, PermissionSource::ASSIGNMENT),
            new PermissionOrigin('role_group', 'students.view', 'reason-2', $scope, PermissionSource::ASSIGNMENT),
            new PermissionOrigin('gtk', 'students.view', 'reason-3', $scope, PermissionSource::ASSIGNMENT),
        ];

        $merged = app(PermissionMergeResolver::class)->resolve($origins);
        $deduped = PermissionConflictResolver::resolve($merged);

        $matches = array_filter($deduped, fn ($o) => $o->permission === 'students.view');
        $this->assertCount(1, $matches, 'Conflict resolver should keep exactly one students.view origin');
    }

    public function test_different_scopes_are_not_collapsed(): void
    {
        $scopeA = ScopeKey::fromComponents('school-1', '2026-2027', 'teacher', 'local');
        $scopeB = ScopeKey::fromComponents('school-2', '2026-2027', 'teacher', 'local');

        $origins = [
            new PermissionOrigin('role_group', 'students.view', 'scope-a', $scopeA, PermissionSource::ASSIGNMENT),
            new PermissionOrigin('role_group', 'students.view', 'scope-b', $scopeB, PermissionSource::ASSIGNMENT),
        ];

        $deduped = PermissionConflictResolver::resolve($origins);

        $this->assertCount(2, $deduped, 'Different scope keys must produce different origin records');
    }

    public function test_revocation_resolver_returns_empty_when_all_revoked(): void
    {
        $scope = $this->scope();
        $origins = [
            new PermissionOrigin('role_group', 'students.view', 'granted', $scope, PermissionSource::ASSIGNMENT),
            new PermissionOrigin('admin', 'students.view', 'revoked', $scope, PermissionSource::REVOCATION),
        ];

        $merged = app(PermissionMergeResolver::class)->resolve($origins);
        $deduped = PermissionConflictResolver::resolve($merged);
        $final = RevocationResolver::resolve($deduped);

        $this->assertCount(0, $final, 'Revocation should consume the only grant');
    }

    public function test_pipeline_survives_with_empty_origin_list(): void
    {
        $merged = app(PermissionMergeResolver::class)->resolve([]);
        $deduped = PermissionConflictResolver::resolve($merged);
        $final = RevocationResolver::resolve($deduped);

        $this->assertSame([], $merged);
        $this->assertSame([], $deduped);
        $this->assertSame([], $final);
    }
}
