<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Authorization\Jobs\BuildSnapshotJob;
use Tests\TestCase;

/**
 * Tests for BuildSnapshotJob (structure only — no DB needed).
 */
final class BuildSnapshotJobTest extends TestCase
{
    public function test_job_has_correct_try_policy(): void
    {
        $job = new BuildSnapshotJob(123);
        $this->assertSame(3, $job->tries);
    }

    public function test_job_has_correct_timeout(): void
    {
        $job = new BuildSnapshotJob(123);
        $this->assertSame(120, $job->timeout);
    }

    public function test_job_has_exponential_backoff(): void
    {
        $job = new BuildSnapshotJob(123);
        $this->assertSame([5, 30, 120], $job->backoff);
    }

    public function test_job_preserves_user_id(): void
    {
        $job = new BuildSnapshotJob(42);
        $this->assertSame(42, $job->userId);
    }

    public function test_job_preserves_string_user_id(): void
    {
        $job = new BuildSnapshotJob('user-uuid-string');
        $this->assertSame('user-uuid-string', $job->userId);
    }
}