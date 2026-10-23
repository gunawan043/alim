<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class RestoreBackup extends Command
{
    protected $signature = 'app:backup:restore
                            {file : Path file backup .sql.gz atau .sqlite}
                            {--force : Wajib diisi untuk benar-benar menimpa database}
                            {--database= : Override nama database tujuan (untuk drill restore)}';

    protected $description = 'Restore database dari backup (gunakan hanya di staging/DR drill).';

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error('File backup tidak ditemukan: '.$file);

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->warn('Restore belum dijalankan. Tambahkan --force setelah memastikan target adalah staging/DR.');

            return self::FAILURE;
        }

        $checksumFile = $file.'.sha256';
        if (is_file($checksumFile)) {
            $expected = trim(explode(' ', trim((string) File::get($checksumFile)))[0]);
            if (! hash_equals($expected, hash_file('sha256', $file))) {
                $this->error('Checksum backup tidak cocok. Restore dibatalkan.');

                return self::FAILURE;
            }
        }

        $driver = DB::connection()->getDriverName();
        $cfg = config('database.connections.'.config('database.default'));

        if ($this->option('database')) {
            $cfg['database'] = (string) $this->option('database');
        }

        try {
            if (str_ends_with($file, '.sqlite')) {
                $database = (string) ($cfg['database'] ?? '');
                if ($database === '' || $database === ':memory:') {
                    throw new \RuntimeException('Restore SQLite membutuhkan file database yang valid.');
                }

                if (is_file($database)) {
                    File::copy($database, $database.'.pre-restore-'.now()->format('Ymd_His'));
                }
                File::copy($file, $database);
            } elseif (str_ends_with($file, '.sql.gz')) {
                $tmpSql = tempnam(sys_get_temp_dir(), 'alim_restore_');
                $unzip = Process::timeout(300)->run('gzip -dc '.escapeshellarg($file).' > '.escapeshellarg($tmpSql));

                if (! $unzip->successful() || ! is_file($tmpSql) || File::size($tmpSql) === 0) {
                    File::delete($tmpSql);
                    throw new \RuntimeException('Isi backup SQL tidak valid.');
                }

                if (in_array($driver, ['mysql', 'mariadb'], true)) {
                    $result = Process::timeout(900)
                        ->env(['MYSQL_PWD' => (string) ($cfg['password'] ?? '')])
                        ->run(sprintf(
                            'mysql --host=%s --port=%s --user=%s %s < %s',
                            escapeshellarg((string) ($cfg['host'] ?? '127.0.0.1')),
                            escapeshellarg((string) ($cfg['port'] ?? '3306')),
                            escapeshellarg((string) ($cfg['username'] ?? '')),
                            escapeshellarg((string) ($cfg['database'] ?? '')),
                            escapeshellarg($tmpSql)
                        ));
                } elseif ($driver === 'pgsql') {
                    $result = Process::timeout(900)
                        ->env(['PGPASSWORD' => (string) ($cfg['password'] ?? '')])
                        ->run(sprintf(
                            'psql --host=%s --port=%s --username=%s --dbname=%s --set ON_ERROR_STOP=1 < %s',
                            escapeshellarg((string) ($cfg['host'] ?? '127.0.0.1')),
                            escapeshellarg((string) ($cfg['port'] ?? '5432')),
                            escapeshellarg((string) ($cfg['username'] ?? '')),
                            escapeshellarg((string) ($cfg['database'] ?? '')),
                            escapeshellarg($tmpSql)
                        ));
                } else {
                    File::delete($tmpSql);
                    throw new \RuntimeException('Restore SQL belum didukung untuk driver: '.$driver);
                }

                File::delete($tmpSql);

                if (! $result->successful()) {
                    throw new \RuntimeException(trim($result->errorOutput()) ?: 'Restore gagal.');
                }
            } else {
                throw new \RuntimeException('Format backup tidak dikenali.');
            }

            $this->info('Restore selesai. Jalankan smoke test/login sebelum membuka akses produksi.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Restore gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
