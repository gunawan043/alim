<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\Exceptions\AuthorizationException;
use App\Authorization\Models\PermissionSnapshot;
use App\Authorization\ValueObjects\ScopeKey;
use Closure;
use Illuminate\Database\ConnectionInterface;

final readonly class SnapshotVersionResolver
{
    private const LOCK_NAMESPACE = 0x4155545A;

    public function __construct(
        private ConnectionInterface $connection,
        private int $lockTimeoutSeconds = 0,
    ) {}

    /**
     * Run a closure inside an exclusive snapshot-build lock + transaction.
     * The lock is scoped per (scopeKey, userId) so unrelated users/scope builds do not serialize.
     *
     * @template T
     * @param Closure(): T $build
     * @return T
     */
    public function run(ScopeKey $scopeKey, int|string $userId, Closure $build): mixed
    {
        $driver = $this->connection->getDriverName();

        return match ($driver) {
            'pgsql' => $this->runPostgres($scopeKey, $userId, $build),
            'mysql', 'mariadb' => $this->runMySql($scopeKey, $userId, $build),
            'sqlite' => $this->runSqlite($scopeKey, $userId, $build),
            default => throw new AuthorizationException(
                "Snapshot version resolver requires PostgreSQL, MySQL, or SQLite. Detected: {$driver}."
            ),
        };
    }

    /**
     * @template T
     * @param Closure(): T $build
     * @return T
     */
    private function runPostgres(ScopeKey $scopeKey, int|string $userId, Closure $build): mixed
    {
        return $this->connection->transaction(function () use ($scopeKey, $userId, $build) {
            $lockKey = $this->lockKeyFor($scopeKey, $userId);

            if ($this->lockTimeoutSeconds > 0) {
                $this->connection->statement(
                    'SET LOCAL lock_timeout = ' . (int) $this->lockTimeoutSeconds . 's'
                );
            }

            $this->connection->statement('SELECT pg_advisory_xact_lock(?, ?)', [
                self::LOCK_NAMESPACE,
                $lockKey,
            ]);

            return $build();
        });
    }

    /**
     * @template T
     * @param Closure(): T $build
     * @return T
     */
    private function runMySql(ScopeKey $scopeKey, int|string $userId, Closure $build): mixed
    {
        return $this->connection->transaction(function () use ($scopeKey, $userId, $build) {
            $lockKey = $this->lockKeyFor($scopeKey, $userId);

            $this->connection->statement('SELECT GET_LOCK(?, 10)', ["authz:{$lockKey}"]);

            try {
                return $build();
            } finally {
                $this->connection->statement('SELECT RELEASE_LOCK(?)', ["authz:{$lockKey}"]);
            }
        });
    }

    /**
     * @template T
     * @param Closure(): T $build
     * @return T
     */
    private function runSqlite(ScopeKey $scopeKey, int|string $userId, Closure $build): mixed
    {
        return $this->connection->transaction(function () use ($scopeKey, $userId, $build) {
            $lockKey = $this->lockKeyFor($scopeKey, $userId);
            $this->connection->statement('BEGIN IMMEDIATE');
            return $build();
        });
    }

    private function lockKeyFor(ScopeKey $scopeKey, int|string $userId): int
    {
        $hash = hash('sha256', (string) $scopeKey . ':' . (string) $userId);
        $unpacked = unpack('J', substr($hash, 0, 8));
        return $unpacked[1] ?? 0;
    }

    public function nextVersion(ScopeKey $scopeKey, int|string $userId): int
    {
        $latest = PermissionSnapshot::query()
            ->where('user_id', $userId)
            ->where('scope_key', (string) $scopeKey)
            ->orderByDesc('id')
            ->value('id');

        return ((int) $latest) + 1;
    }
}