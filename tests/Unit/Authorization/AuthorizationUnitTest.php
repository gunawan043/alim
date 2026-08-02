<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Authorization\DTO\PermissionBag;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\DTO\SnapshotFingerprint;
use App\Authorization\DTO\SnapshotMetadata;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\Enums\SnapshotStatus;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Authorization\ValueObjects\ScopeKey;
use DateTimeImmutable;
use Tests\TestCase;

/**
 * Unit tests for Authorization DTOs, ValueObjects, and Enums.
 */
final class AuthorizationUnitTest extends TestCase
{
    // ── SnapshotMetadata ───────────────────────────────────────

    public function test_snapshot_metadata(): void
    {
        $scopeKey = ScopeKey::fromComponents('s1', 'ay1', 'r1');
        $meta = new SnapshotMetadata(
            createdAt: new DateTimeImmutable,
            scopeKey: $scopeKey,
            version: 42,
            status: SnapshotStatus::ACTIVE,
        );

        $this->assertSame(42, $meta->version);
        $this->assertSame($scopeKey, $meta->scopeKey);
        $this->assertSame(SnapshotStatus::ACTIVE, $meta->status);
    }

    // ── PermissionOrigin ───────────────────────────────────────

    public function test_permission_origin_system_provider(): void
    {
        $scopeKey = ScopeKey::fromComponents('s1', 'ay1', 'r1');
        $origin = new PermissionOrigin('system', 'read', 'default role', $scopeKey);

        $this->assertSame('system', $origin->provider);
        $this->assertSame('read', $origin->permission);
        $this->assertSame(PermissionSource::EMPLOYMENT, $origin->source);
    }

    public function test_permission_origin_custom_source(): void
    {
        $scopeKey = ScopeKey::fromComponents('s1', 'ay1', 'r1');
        $origin = new PermissionOrigin(
            'policy',
            'admin',
            'manual grant',
            $scopeKey,
            PermissionSource::MANUAL,
        );

        $this->assertSame(PermissionSource::MANUAL, $origin->source);
    }

    // ── PermissionBag ──────────────────────────────────────────

    public function test_permission_bag_contains_permissions(): void
    {
        $scopeKey = ScopeKey::fromComponents('s1', 'ay1', 'r1');
        $meta = new SnapshotMetadata(
            createdAt: new DateTimeImmutable,
            scopeKey: $scopeKey,
            version: 1,
            status: SnapshotStatus::ACTIVE,
        );

        $bag = new PermissionBag(['read', 'write'], [], 'fp123', null, $meta);

        $this->assertEquals(['read', 'write'], $bag->getPermissions());
        $this->assertEquals([], $bag->getRevoked());
        $this->assertNull($bag->getExpiresAt());
        $this->assertEquals($meta, $bag->getMetadata());
        $this->assertEquals('fp123', $bag->getFingerprint());
    }

    public function test_permission_bag_with_origins(): void
    {
        $scopeKey = ScopeKey::fromComponents('s1', 'ay1', 'r1');
        $meta = new SnapshotMetadata(
            createdAt: new DateTimeImmutable,
            scopeKey: $scopeKey,
            version: 1,
            status: SnapshotStatus::ACTIVE,
        );

        $origin = new PermissionOrigin('role', 'admin', 'role assignment', $scopeKey);
        $bag = new PermissionBag(['admin'], ['read'], 'fp456', null, $meta, [$origin]);

        $this->assertCount(1, $bag->getOrigins());
        $this->assertEquals(['admin'], $bag->getPermissions());
        $this->assertEquals(['read'], $bag->getRevoked());
    }

    // ── SnapshotFingerprint ────────────────────────────────────

    public function test_snapshot_fingerprint(): void
    {
        $fp = new SnapshotFingerprint('abcdef1234567890', 'sha256', new DateTimeImmutable);

        $this->assertSame('abcdef1234567890', $fp->hash);
        $this->assertSame('sha256', $fp->algorithm);
        $this->assertInstanceOf(DateTimeImmutable::class, $fp->createdAt);
    }

    // ── OrganizationContext ────────────────────────────────────

    public function test_organization_context_to_scope_key(): void
    {
        $ctx = new OrganizationContext('school-a', 'ay-2025', 'default');
        $scopeKey = $ctx->toScopeKey();

        $this->assertInstanceOf(ScopeKey::class, $scopeKey);

        // Different params → different scope key
        $ctx2 = new OrganizationContext('school-b', 'ay-2025', 'default');
        $this->assertNotSame($ctx->toScopeKey(), $ctx2->toScopeKey());
    }

    // ── ScopeKey ───────────────────────────────────────────────

    public function test_scope_key_from_components(): void
    {
        $key = ScopeKey::fromComponents('school-1', 'ay-2025', 'default');
        $this->assertInstanceOf(ScopeKey::class, $key);
    }

    public function test_scope_key_equals(): void
    {
        $k1 = ScopeKey::fromComponents('school-1', 'ay-2025', 'default');
        $k2 = ScopeKey::fromComponents('school-1', 'ay-2025', 'default');
        $k3 = ScopeKey::fromComponents('school-2', 'ay-2025', 'default');

        $this->assertTrue($k1->equals($k2));
        $this->assertFalse($k1->equals($k3));
    }

    public function test_scope_key_to_string(): void
    {
        $key = ScopeKey::fromComponents('school-1', 'ay-2025', 'default');
        $str = (string) $key;
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $str);
    }

    public function test_scope_key_invalid_hash_throws(): void
    {
        $this->expectException(\App\Authorization\Exceptions\InvalidScopeException::class);
        ScopeKey::fromHash('not-a-valid-hash!!!');
    }

    // ── Enums ───────────────────────────────────────��──────────

    public function test_snapshot_status_enum_values(): void
    {
        $this->assertSame('active', SnapshotStatus::ACTIVE->value);
        $this->assertSame('archived', SnapshotStatus::ARCHIVED->value);
        $this->assertSame('failed', SnapshotStatus::FAILED->value);
    }

    public function test_permission_source_enum_values(): void
    {
        $values = PermissionSource::cases();
        $this->assertNotEmpty($values);

        $labels = array_map(fn ($e) => $e->value, $values);
        $this->assertContains('employment', $labels);
        $this->assertContains('assignment', $labels);
        $this->assertContains('delegation', $labels);
        $this->assertContains('revocation', $labels);
        $this->assertContains('manual', $labels);
    }
}
