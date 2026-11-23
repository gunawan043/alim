<?php

namespace App\Console\Commands;

use App\Models\ApprovalCommand;
use App\Models\ConnectActorIdentity;
use App\Models\ConnectSsoHandoff;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Verifikasi objek database yang dibutuhkan integrasi ALIM Connect dan alur
 * asesmen (kisi-kisi → bank soal → paket → cetak).
 *
 * `migrate:status` pernah melaporkan "Ran" padahal tabel belum ada, dan
 * sebaliknya migrasi yang ikut ter-deploy tetap "Pending". Karena itu periksa
 * keberadaan tabel/kolom sebenarnya, bukan hanya catatan migrasi.
 *
 * Exit 0 = semua objek ada. Exit 1 = ada yang hilang (deploy belum lengkap).
 */
class VerifyConnectSchema extends Command
{
    protected $signature = 'alim:verify-connect-schema';

    protected $description = 'Verifikasi tabel/kolom integrasi ALIM Connect dan alur asesmen benar-benar ada di database';

    /**
     * @return array<string, list<string>>
     */
    private function requiredColumns(): array
    {
        return [
            (new ConnectActorIdentity)->getTable() => [
                'id', 'channel', 'external_identity', 'user_id', 'is_active', 'verified_at',
            ],
            (new ApprovalCommand)->getTable() => [
                'id', 'command_id', 'idempotency_key', 'api_version', 'permit_id',
                'actor_user_id', 'actor_external_identity', 'decision', 'source',
                'external_message_id', 'payload_hash', 'response_code', 'response_body',
                'processed_at',
            ],
            (new ConnectSsoHandoff)->getTable() => [
                'id', 'user_id', 'code_hash', 'audience', 'scope', 'expires_at',
                'used_at', 'used_ip', 'used_user_agent',
            ],
            // Alur asesmen. `bank_soal_tp` pernah hilang karena migrasinya
            // melewati SQLite, sehingga halaman bank soal gagal 500.
            'bank_soal_tp' => ['bank_soal_id', 'tp_id'],
            'kisi_kisi_soal' => ['id', 'school_id', 'subject_id', 'judul'],
            'kisi_kisi_soal_items' => ['id', 'kisi_kisi_soal_id', 'tp_id'],
            'paket_soal' => ['id', 'kisi_kisi_soal_id', 'judul', 'kode_paket'],
            'paket_soal_items' => ['id', 'paket_soal_id', 'soal_id'],
            'soal' => ['id', 'bank_soal_id', 'pertanyaan'],
            'soal_options' => ['id', 'soal_id', 'teks_opsi', 'is_correct'],
        ];
    }

    public function handle(): int
    {
        $missing = [];
        $rows = [];

        foreach ($this->requiredColumns() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $missing[] = "tabel {$table}";
                $rows[] = [$table, 'TIDAK ADA', '—'];

                continue;
            }

            $absent = [];
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $absent[] = $column;
                }
            }

            if ($absent !== []) {
                $missing[] = "kolom {$table}.".implode(', ', $absent);
            }

            $rows[] = [$table, $absent === [] ? 'OK' : 'KOLOM KURANG', $absent === [] ? count($columns).' kolom' : implode(', ', $absent)];
        }

        $this->table(['Tabel', 'Status', 'Keterangan'], $rows);

        if ($missing === []) {
            $this->info('Skema integrasi ALIM Connect lengkap.');

            return self::SUCCESS;
        }

        $this->error('Skema integrasi ALIM Connect BELUM lengkap:');
        foreach ($missing as $item) {
            $this->line('  - '.$item);
        }
        $this->line('');
        $this->line('Jalankan: php artisan migrate --force');
        $this->line('Bila sudah "Ran" tetapi objek belum ada, jalankan migrasinya secara manual.');

        return self::FAILURE;
    }
}
