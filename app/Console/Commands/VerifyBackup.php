<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyBackup extends Command
{
    protected $signature = 'app:backup:verify
                            {file? : Path file backup}
                            {--latest : Gunakan file backup terbaru jika file tidak diberikan}';

    protected $description = 'Verifikasi checksum dan integritas file backup database.';

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if ($file === '' || $this->option('latest')) {
            $file = (string) collect(File::glob(storage_path('app/backups').'/backup_*.{sql.gz,sqlite}', GLOB_BRACE))
                ->sortByDesc(fn ($path) => File::lastModified($path))
                ->first();
        }

        if ($file === '' || ! is_file($file)) {
            $this->error('File backup tidak ditemukan.');

            return self::FAILURE;
        }

        $checksumFile = $file.'.sha256';
        if (! is_file($checksumFile)) {
            $this->error('File checksum tidak ditemukan: '.$checksumFile);

            return self::FAILURE;
        }

        $expected = trim(explode(' ', trim((string) File::get($checksumFile)))[0]);
        $actual = hash_file('sha256', $file);

        if (! hash_equals($expected, $actual)) {
            $this->error('Checksum TIDAK cocok. Backup rusak atau berubah.');

            return self::FAILURE;
        }

        if (str_ends_with($file, '.sql.gz')) {
            $handle = gzopen($file, 'rb');
            $sample = $handle ? gzread($handle, 256) : '';
            if ($handle) {
                gzclose($handle);
            }

            if (! is_string($sample) || $sample === '') {
                $this->error('Gzip tidak dapat dibaca atau kosong.');

                return self::FAILURE;
            }
        }

        $this->info('Backup valid: '.$file);
        $this->line('Checksum: '.$actual);
        $this->line('Ukuran: '.number_format(File::size($file) / 1024, 1).' KB');

        return self::SUCCESS;
    }
}
