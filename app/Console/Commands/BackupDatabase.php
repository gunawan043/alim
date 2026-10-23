<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use ZipArchive;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup
                            {--keep=14 : Jumlah backup terbaru yang disimpan}
                            {--with-files : Ikutkan storage/app/public dan storage/app/arsip}
                            {--verify : Verifikasi hasil backup setelah dibuat}';

    protected $description = 'Buat backup database (dan opsional file asrama) dengan checksum.';

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $connection = (string) config('database.default');
        $driver = DB::connection($connection)->getDriverName();
        $stamp = now()->format('Ymd_His');
        $base = $directory.'/backup_'.$stamp.'_'.$driver;

        try {
            $file = match ($driver) {
                'mysql', 'mariadb' => $this->backupMysql($connection, $base.'.sql.gz'),
                'pgsql' => $this->backupPgsql($connection, $base.'.sql.gz'),
                'sqlite' => $this->backupSqlite($connection, $base.'.sqlite'),
                default => throw new \RuntimeException('Driver database belum didukung: '.$driver),
            };

            File::put($file.'.sha256', hash_file('sha256', $file).'  '.basename($file).PHP_EOL);
            $this->info('Backup database: '.$file);

            if ($this->option('with-files')) {
                $zip = $this->backupFiles($base.'_files.zip');
                if ($zip) {
                    File::put($zip.'.sha256', hash_file('sha256', $zip).'  '.basename($zip).PHP_EOL);
                    $this->info('Backup file: '.$zip);
                }
            }

            if ($this->option('verify')) {
                $this->call('app:backup:verify', ['file' => $file]);
            }

            $this->prune((int) $this->option('keep'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function backupMysql(string $connection, string $target): string
    {
        $cfg = config('database.connections.'.$connection);
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --routines --triggers --default-character-set=utf8mb4 %s',
            escapeshellarg((string) ($cfg['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($cfg['port'] ?? '3306')),
            escapeshellarg((string) ($cfg['username'] ?? '')),
            escapeshellarg((string) ($cfg['database'] ?? '')),
        );

        $tmp = $target.'.tmp';
        $result = Process::timeout(900)
            ->env(['MYSQL_PWD' => (string) ($cfg['password'] ?? '')])
            ->run($command.' > '.escapeshellarg($tmp));

        if (! $result->successful()) {
            File::delete($tmp);
            throw new \RuntimeException(trim($result->errorOutput()) ?: 'mysqldump gagal.');
        }

        $gzip = Process::timeout(300)->run('gzip -c '.escapeshellarg($tmp).' > '.escapeshellarg($target));
        File::delete($tmp);

        if (! $gzip->successful()) {
            throw new \RuntimeException(trim($gzip->errorOutput()) ?: 'Kompresi backup gagal.');
        }

        return $target;
    }

    private function backupPgsql(string $connection, string $target): string
    {
        $cfg = config('database.connections.'.$connection);
        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --no-owner --no-privileges %s',
            escapeshellarg((string) ($cfg['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($cfg['port'] ?? '5432')),
            escapeshellarg((string) ($cfg['username'] ?? '')),
            escapeshellarg((string) ($cfg['database'] ?? '')),
        );

        $tmp = $target.'.tmp';
        $result = Process::timeout(900)
            ->env(['PGPASSWORD' => (string) ($cfg['password'] ?? '')])
            ->run($command.' > '.escapeshellarg($tmp));

        if (! $result->successful()) {
            File::delete($tmp);
            throw new \RuntimeException(trim($result->errorOutput()) ?: 'pg_dump gagal.');
        }

        $gzip = Process::timeout(300)->run('gzip -c '.escapeshellarg($tmp).' > '.escapeshellarg($target));
        File::delete($tmp);

        if (! $gzip->successful()) {
            throw new \RuntimeException(trim($gzip->errorOutput()) ?: 'Kompresi backup gagal.');
        }

        return $target;
    }

    private function backupSqlite(string $connection, string $target): string
    {
        $database = (string) config('database.connections.'.$connection.'.database');

        if ($database === '' || $database === ':memory:' || ! is_file($database)) {
            throw new \RuntimeException('File SQLite tidak ditemukan: '.$database);
        }

        File::copy($database, $target);

        return $target;
    }

    private function backupFiles(string $target): ?string
    {
        $sources = array_filter([
            storage_path('app/public'),
            storage_path('app/arsip'),
        ], 'is_dir');

        if ($sources === []) {
            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Tidak dapat membuat arsip file backup.');
        }

        foreach ($sources as $source) {
            $baseName = basename($source);
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $relative = $baseName.'/'.ltrim(str_replace($source, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                $zip->addFile($file->getPathname(), $relative);
            }
        }

        $zip->close();

        return $target;
    }

    private function prune(int $keep): void
    {
        $keep = max(1, $keep);
        $files = collect(File::glob(storage_path('app/backups').'/backup_*'))
            ->reject(fn ($file) => str_ends_with($file, '.sha256'))
            ->sortByDesc(fn ($file) => File::lastModified($file))
            ->values();

        foreach ($files->slice($keep) as $file) {
            File::delete($file, $file.'.sha256');
        }
    }
}
