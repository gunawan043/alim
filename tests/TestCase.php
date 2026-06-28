<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Override dropAllTables to use our reliable command
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
