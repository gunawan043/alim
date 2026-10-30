<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SupabaseCheck extends Command
{
    protected $signature = 'supabase:check';

    protected $description = 'Cek konfigurasi dan koneksi database Supabase PostgreSQL.';

    public function handle(): int
    {
        if (! extension_loaded('pdo_pgsql')) {
            $this->error('Ekstensi PHP pdo_pgsql belum aktif. Aktifkan sebelum memakai Supabase.');

            return self::FAILURE;
        }

        $connectionConfig = config('database.connections.supabase', []);

        if (blank($connectionConfig['url'] ?? null) && blank($connectionConfig['host'] ?? null)) {
            $this->error('SUPABASE_DB_URL (atau SUPABASE_DB_HOST/USERNAME/PASSWORD) belum diisi di .env.');

            return self::FAILURE;
        }

        $this->info('Menguji koneksi ke Supabase (connection: supabase)...');

        try {
            $connection = DB::connection('supabase');
            $version = $connection->selectOne('select version() as version');
            $database = $connection->selectOne('select current_database() as name');
            $schema = $connection->selectOne('select current_schema() as name');
            $tables = $connection->selectOne("select count(*) as total from information_schema.tables where table_schema = current_schema() and table_type = 'BASE TABLE'");
            $migrations = $connection->getSchemaBuilder()->hasTable('migrations')
                ? $connection->table('migrations')->count()
                : 0;

            $this->newLine();
            $this->table(['Item', 'Nilai'], [
                ['Koneksi', 'OK'],
                ['Database', $database->name ?? '-'],
                ['Schema', $schema->name ?? '-'],
                ['Jumlah tabel', $tables->total ?? 0],
                ['Migration tercatat', $migrations],
                ['Versi PostgreSQL', strtok((string) ($version->version ?? '-'), ',')],
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Koneksi Supabase gagal: '.$e->getMessage());
            $this->line('Periksa URL Session Pooler, password, SSL, dan izin database.');

            return self::FAILURE;
        }
    }
}
