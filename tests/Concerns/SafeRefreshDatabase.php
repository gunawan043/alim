<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabaseState;

trait SafeRefreshDatabase
{
    /**
     * Setup the test database using our patched wipe command.
     * Each test method runs in a transaction (rolled back after), and DB
     * is re-migrated once via setUpSafeDatabase's class-level guard.
     */
    public function setUpSafeDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            // Drop all tables in small batches (avoids MySQL deadlocks)
            \Artisan::call('db:safe-wipe', ['--database' => 'mysql_test', '--force' => true]);
            // Re-run migrations
            \Artisan::call('migrate', ['--database' => 'mysql_test', '--force' => true]);
            RefreshDatabaseState::$migrated = true;
        }

        $this->beginSafeDatabaseTransaction();
        $this->seedSafeFixtures();
    }

    public function beginSafeDatabaseTransaction(): void
    {
        $conn = $this->app->make('db')->connection();
        $conn->beginTransaction();
    }

    public function tearDownSafeDatabase(): void
    {
        $conn = $this->app->make('db')->connection();
        while ($conn->transactionLevel() > 0) {
            $conn->rollBack();
        }
    }

    /**
     * Hook for tests to insert shared fixture rows.
     */
    protected function seedSafeFixtures(): void
    {
        // Override in tests
    }
}