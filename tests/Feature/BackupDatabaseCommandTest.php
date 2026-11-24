<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use Illuminate\Process\Factory;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regresi jalur cadangan MySQL/PostgreSQL (Cycle 9.7).
 *
 * Yang dijaga:
 *  - kata sandi tidak pernah menjadi argumen perintah maupun nilai environment;
 *  - berkas opsi sementara berpermisi 0600 selama proses dump berjalan;
 *  - berkas sementara dihapus, baik dump sukses maupun gagal;
 *  - jalur SQLite tidak berubah (menyalin berkas, tanpa proses eksternal).
 */
class BackupDatabaseCommandTest extends TestCase
{
    private function probeConnections(): void
    {
        foreach (['mysql', 'pgsql'] as $driver) {
            config()->set("database.connections.{$driver}_probe", [
                'driver' => $driver,
                'host' => '127.0.0.1',
                'port' => $driver === 'mysql' ? '3306' : '5432',
                'database' => 'probe_db',
                'username' => 'probe_user',
                'password' => 'PROBE-SECRET-PASSWORD',
            ]);
        }
    }

    /**
     * Jalankan backupMysql/backupPgsql dengan PendingProcess palsu yang merekam
     * perintah, environment, dan keadaan berkas opsi saat dump berjalan.
     *
     * @return array<string, mixed>
     */
    private function runBackupDriver(string $method, int $exitCode): array
    {
        $this->probeConnections();

        $observed = [
            'line' => '',
            'env' => [],
            'file' => null,
            'perm' => null,
            'contents' => null,
            'exists_after' => null,
            'error' => null,
        ];

        Process::swap(new class($observed, $exitCode) extends PendingProcess
        {
            public function __construct(private array &$observed, private int $exitCode)
            {
                parent::__construct(app(Factory::class));
            }

            public function run(array|string|null $command = null, ?callable $output = null)
            {
                $this->observed['line'] = (string) $command;
                $this->observed['env'] = $this->environment;

                if (preg_match('/--defaults-extra-file=(\'[^\']+\'|\S+)/', (string) $command, $m)) {
                    $path = trim($m[1], "'");
                    clearstatcache(true, $path);
                    $this->observed['file'] = $path;
                    $this->observed['perm'] = file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : null;
                    $this->observed['contents'] = file_exists($path) ? (string) file_get_contents($path) : null;
                } elseif (isset($this->environment['PGPASSFILE'])) {
                    // pg_dump menerima jalur kata sandi lewat environment.
                    $path = (string) $this->environment['PGPASSFILE'];
                    clearstatcache(true, $path);
                    $this->observed['file'] = $path;
                    $this->observed['perm'] = file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : null;
                    $this->observed['contents'] = file_exists($path) ? (string) file_get_contents($path) : null;
                }

                return Process::result('', $this->exitCode === 0 ? '' : 'dump gagal', $this->exitCode);
            }
        });

        $command = app(BackupDatabase::class);
        $ref = new ReflectionMethod($command, $method);
        $ref->setAccessible(true);

        $target = sys_get_temp_dir().'/alim-test-'.bin2hex(random_bytes(4)).'.sql.gz';

        try {
            $ref->invoke($command, $method === 'backupMysql' ? 'mysql_probe' : 'pgsql_probe', $target);
        } catch (\Throwable $e) {
            $observed['error'] = $e;
        }

        if ($observed['file'] !== null) {
            clearstatcache(true, $observed['file']);
            $observed['exists_after'] = file_exists($observed['file']);
            @unlink($observed['file']);
        }

        foreach (glob($target.'*') ?: [] as $leftover) {
            @unlink($leftover);
        }

        Process::swap(new Factory);

        return $observed;
    }

    public function test_mysql_password_never_in_arguments_or_environment(): void
    {
        $o = $this->runBackupDriver('backupMysql', 0);

        $this->assertStringContainsString('mysqldump', $o['line']);
        $this->assertStringNotContainsString('PROBE-SECRET-PASSWORD', $o['line']);
        $this->assertStringNotContainsString('--user=', $o['line']);
        $this->assertArrayNotHasKey('MYSQL_PWD', $o['env']);
        $this->assertStringNotContainsString('PROBE-SECRET-PASSWORD', json_encode($o['env']));
    }

    public function test_mysql_option_file_is_0600_and_removed_after_dump(): void
    {
        $o = $this->runBackupDriver('backupMysql', 0);

        $this->assertSame('0600', $o['perm']);
        $this->assertStringContainsString('password=PROBE-SECRET-PASSWORD', $o['contents']);
        $this->assertStringContainsString('user=probe_user', $o['contents']);
        $this->assertStringNotContainsString('database=', $o['contents']);
        $this->assertFalse($o['exists_after'], 'berkas opsi harus dihapus setelah mysqldump selesai');
    }

    public function test_mysql_option_file_removed_even_when_dump_fails(): void
    {
        $o = $this->runBackupDriver('backupMysql', 2);

        $this->assertNotNull($o['error']);
        $this->assertFalse($o['exists_after']);
    }

    public function test_pgsql_password_only_in_0600_passfile_not_pgpassword(): void
    {
        $o = $this->runBackupDriver('backupPgsql', 0);

        $this->assertStringContainsString('pg_dump', $o['line']);
        $this->assertStringNotContainsString('PROBE-SECRET-PASSWORD', $o['line']);
        $this->assertArrayHasKey('PGPASSFILE', $o['env']);
        $this->assertArrayNotHasKey('PGPASSWORD', $o['env']);
        $this->assertStringContainsString('PROBE-SECRET-PASSWORD', $o['contents']);
        $this->assertFalse($o['exists_after']);
    }

    public function test_pgsql_passfile_removed_even_when_dump_fails(): void
    {
        $o = $this->runBackupDriver('backupPgsql', 1);

        $this->assertNotNull($o['error']);
        $this->assertFalse($o['exists_after']);
    }

    public function test_sqlite_path_still_copies_file_without_external_process(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'alim-sqlite-src');
        file_put_contents($source, 'SQLite format 3'.str_repeat("\0", 84));

        config()->set('database.connections.sqlite_probe', [
            'driver' => 'sqlite',
            'database' => $source,
        ]);

        Process::preventStrayProcesses();

        $command = app(BackupDatabase::class);
        $ref = new ReflectionMethod($command, 'backupSqlite');
        $ref->setAccessible(true);

        $target = sys_get_temp_dir().'/alim-test-'.bin2hex(random_bytes(4)).'.sqlite';
        $returned = $ref->invoke($command, 'sqlite_probe', $target);

        $this->assertSame($target, $returned);
        $this->assertSame(hash_file('sha256', $source), hash_file('sha256', $target));
        $this->assertSame(file_get_contents($source), file_get_contents($target));

        @unlink($source);
        @unlink($target);
    }
}
