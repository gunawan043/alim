<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use Tests\TestCase;

/**
 * Tests for Authorization helpers and configuration.
 */
final class GlobalHelpersTest extends TestCase
{
    public function test_helpers_file_exists(): void
    {
        $this->assertTrue(
            file_exists(__DIR__ . '/../../../app/Authorization/helpers.php'),
            'app/Authorization/helpers.php should exist.'
        );
    }

    public function test_helpers_file_contains_expected_functions(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../app/Authorization/helpers.php');

        $this->assertStringContainsString('function canPermission', $source);
        $this->assertStringContainsString('function cannotPermission', $source);
        $this->assertStringContainsString('function permissionSnapshot', $source);
    }

    public function test_config_authorization_has_defaults(): void
    {
        $config = config('authorization');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('snapshot_ttl', $config);
        $this->assertArrayHasKey('cache_ttl', $config);
        $this->assertArrayHasKey('rebuild_queue', $config);
    }

    public function test_config_authorization_default_values_are_sane(): void
    {
        $config = config('authorization');

        $this->assertIsInt($config['snapshot_ttl']);
        $this->assertGreaterThan(0, $config['snapshot_ttl']);

        $this->assertIsInt($config['cache_ttl']);
        $this->assertGreaterThan(0, $config['cache_ttl']);

        $this->assertIsArray($config['rebuild_queue']);
    }
}