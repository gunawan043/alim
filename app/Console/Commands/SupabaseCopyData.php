<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SupabaseCopyData extends Command
{
    protected $signature = 'supabase:copy-data
                            {--tables= : Daftar tabel dipisah koma; kosong = semua tabel MySQL yang berisi data}
                            {--truncate : Kosongkan tabel aplikasi Supabase sebelum copy}
                            {--dry-run : Hanya tampilkan rencana, tidak menulis}
                            {--chunk=500 : Jumlah baris per insert}';

    protected $description = 'Salin data MySQL lokal ke Supabase untuk staging/initial import.';

    public function handle(): int
    {
        $admin = DB::connection('supabase_admin');
        $source = DB::connection();
        $driver = $source->getDriverName();

        if ($driver !== 'mysql') {
            $this->error('Koneksi default harus MySQL. DB_CONNECTION saat ini: '.$driver);

            return self::FAILURE;
        }

        if (! Schema::connection('supabase_admin')->hasTable('migrations')) {
            $this->error('Schema Supabase belum siap.');

            return self::FAILURE;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $truncate = (bool) $this->option('truncate');
        $requested = $this->option('tables');
        $tables = $requested
            ? collect(explode(',', (string) $requested))->map(fn ($t) => trim($t))->filter()->values()->all()
            : $this->sourceTables($source);

        $this->info('Sumber: MySQL '.$source->getDatabaseName());
        $this->info('Tujuan: Supabase '.config('database.connections.supabase_admin.database'));
        $this->info('Tabel kandidat: '.count($tables));

        if ($dryRun) {
            foreach ($tables as $table) {
                try {
                    $this->line(sprintf('  %-55s %s rows', $table, $source->table($table)->count()));
                } catch (Throwable $e) {
                    $this->warn("  {$table}: ".$e->getMessage());
                }
            }

            return self::SUCCESS;
        }

        // Foreign key triggers dinonaktifkan selama import. RLS tetap tidak
        // mengganggu karena koneksi admin adalah owner tabel.
        $admin->statement('SET session_replication_role = replica');

        if ($truncate) {
            $this->warn('Mengosongkan tabel aplikasi di Supabase...');
            $publicTables = collect($admin->select(
                "select tablename from pg_tables where schemaname = 'public'"
            ))->pluck('tablename')->reject(fn ($t) => $t === 'migrations');

            foreach ($publicTables as $table) {
                $admin->statement('TRUNCATE TABLE "'.str_replace('"', '""', $table).'" CASCADE');
            }
            $this->line('  Tabel dikosongkan: '.$publicTables->count());
            // Pooler dapat mengganti sesi selama truncate; set ulang setelahnya.
            $admin->statement('SET session_replication_role = replica');
        }

        $copied = [];
        $failed = [];

        foreach ($tables as $table) {
            $bar = null;
            try { $admin->statement('SET session_replication_role = replica'); } catch (Throwable $e) {}
            if ($table === 'migrations') {
                continue;
            }

            try {
                if (! Schema::connection('supabase_admin')->hasTable($table)) {
                    $this->warn("SKIP {$table}: tabel tujuan tidak ada.");
                    continue;
                }

                $sourceColumns = collect($source->getSchemaBuilder()->getColumnListing($table));
                $targetMeta = collect($admin->select(
                    'select column_name, data_type, is_generated, is_identity
                     from information_schema.columns
                     where table_schema = current_schema() and table_name = ?',
                    [$table]
                ))->keyBy('column_name');

                $columns = $sourceColumns
                    ->filter(fn ($column) => $targetMeta->has($column))
                    ->reject(fn ($column) => in_array(strtoupper((string) $targetMeta[$column]->is_generated), ['ALWAYS', 'BY DEFAULT'], true))
                    ->values()
                    ->all();

                if ($columns === []) {
                    $this->warn("SKIP {$table}: tidak ada kolom yang cocok.");
                    continue;
                }

                $total = $source->table($table)->count();
                if ($total === 0) {
                    $this->line("SKIP {$table}: kosong.");
                    continue;
                }

                $this->line("COPY {$table} ({$total} rows)...");
                $bar = $this->output->createProgressBar($total);
                $bar->start();
                $buffer = [];
                $tableCopied = 0;

                $source->table($table)->orderBy($columns[0])->chunk($chunk, function ($rows) use (
                    &$buffer, &$tableCopied, $admin, $table, $columns, $targetMeta, $bar
                ) {
                    foreach ($rows as $row) {
                        $payload = [];
                        foreach ($columns as $column) {
                            $value = $row->{$column};
                            $type = strtolower((string) ($targetMeta[$column]->data_type ?? ''));

                            if ($value !== null && $type === 'boolean') {
                                $value = (bool) $value;
                            } elseif ($value === '' && in_array($type, ['json', 'jsonb', 'uuid'], true)) {
                                $value = null;
                            }

                            $payload[$column] = $value;
                        }
                        $buffer[] = $payload;
                    }

                    if ($buffer !== []) {
                        $admin->statement('SET session_replication_role = replica');
                        $admin->table($table)->insert($buffer);
                        $tableCopied += count($buffer);
                        $bar->advance(count($buffer));
                        $buffer = [];
                    }
                });

                if ($buffer !== []) {
                    $admin->table($table)->insert($buffer);
                    $tableCopied += count($buffer);
                    $bar->advance(count($buffer));
                }

                $bar->finish();
                $this->newLine();
                $copied[$table] = ['source' => $total, 'target' => $tableCopied];
            } catch (Throwable $e) {
                $this->newLine();
                $this->error("FAIL {$table}: ".$e->getMessage());
                $failed[$table] = $e->getMessage();
                $bar?->clear();
            }
        }

        $admin->statement('SET session_replication_role = default');
        $this->resetSequences($admin);

        $this->newLine(2);
        $this->info('Selesai. Tabel berhasil: '.count($copied).', gagal: '.count($failed));

        if ($copied !== []) {
            $this->table(['Tabel', 'Sumber', 'Tujuan'], collect($copied)->map(fn ($v, $k) => [$k, $v['source'], $v['target']])->values()->all());
        }

        if ($failed !== []) {
            $this->error('Tabel gagal:');
            foreach ($failed as $table => $message) {
                $this->line("  {$table}: {$message}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function sourceTables($connection): array
    {
        $rows = $connection->select(
            'select table_name, table_rows from information_schema.tables
             where table_schema = database() and table_rows > 0 and table_type = ?',
            ['BASE TABLE']
        );

        return collect($rows)
            ->pluck('table_name')
            ->reject(fn ($table) => $table === 'migrations')
            ->sort()
            ->values()
            ->all();
    }

    private function resetSequences($connection): void
    {
        $columns = $connection->select(
            "select table_name, column_name
             from information_schema.columns
             where table_schema = current_schema()
               and column_default like 'nextval(%'"
        );

        foreach ($columns as $column) {
            $table = str_replace('"', '""', $column->table_name);
            $name = str_replace('"', '""', $column->column_name);

            try {
                $connection->statement(sprintf(
                    'SELECT setval(pg_get_serial_sequence(\'"%s"\', \'%s\'), COALESCE((SELECT MAX("%s") FROM "%s"), 0) + 1, false)',
                    $table, $name, $name, $table
                ));
            } catch (Throwable $e) {
                $this->warn("Sequence reset gagal untuk {$column->table_name}.{$column->column_name}: ".$e->getMessage());
            }
        }
    }
}
