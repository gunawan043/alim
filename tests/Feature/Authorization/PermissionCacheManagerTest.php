<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Authorization\Contracts\PermissionCacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for PermissionCacheManager.
 * Requires MySQL to be running. Skipped if DB is unavailable.
 */
final class PermissionCacheManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_manager_is_bound_in_container(): void
    {
        $manager = $this->app->make(PermissionCacheManager::class);
        $this->assertInstanceOf(PermissionCacheManager::class, $manager);
    }

    public function test_cache_manager_methods_exist(): void
    {
        $manager = $this->app->make(PermissionCacheManager::class);

        $this->assertTrue(method_exists($manager, 'remember'));
        $this->assertTrue(method_exists($manager, 'get'));
        $this->assertTrue(method_exists($manager, 'put'));
        $this->assertTrue(method_exists($manager, 'forget'));
        $this->assertTrue(method_exists($manager, 'forgetUser'));
        $this->assertTrue(method_exists($manager, 'forgetScope'));
    }

    public function test_cache_manager_is_taggable(): void
    {
        $manager = $this->app->make(PermissionCacheManager::class);
        $this->assertIsBool($manager->isTaggable());
    }

    public function test_cache_manager_get_returns_null_for_uncached(): void
    {
        $manager = $this->app->make(PermissionCacheManager::class);

        $result = $manager->get('nonexistent', 'scope-key-not-set');
        $this->assertNull($result);
    }
}
