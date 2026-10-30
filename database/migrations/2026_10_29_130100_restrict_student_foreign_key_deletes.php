<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0 — Cegah penghapusan santri menghapus riwayatnya secara berantai.
 *
 * Hampir semua foreign key yang menunjuk ke students memakai ON DELETE CASCADE,
 * sehingga satu baris santri yang terhapus akan ikut melenyapkan mutasi, rombel,
 * nilai, kehadiran, alumni, dan audit. Migrasi ini mengubah seluruh FK tersebut
 * menjadi ON DELETE RESTRICT: database sendiri akan menolak hard delete santri
 * yang masih memiliki riwayat.
 *
 * Catatan: FK yang memang ON DELETE SET NULL (arsip dokumen, insiden lab,
 * wali default) tidak diubah karena tidak menghapus data.
 */
return new class extends Migration
{
    /** FK yang sudah RESTRICT sejak awal — tidak boleh diubah saat rollback. */
    private const PRA_EXISTING_RESTRICT = [
        'ljk_participants',
        'exam_attempts',
        'uks_patients',
    ];

    public function up(): void
    {
        if (! $this->supportsInformationSchema()) {
            return;
        }

        $this->rebuild($this->studentForeignKeys('CASCADE'), 'restrict');
    }

    public function down(): void
    {
        if (! $this->supportsInformationSchema()) {
            return;
        }

        $fks = array_values(array_filter(
            $this->studentForeignKeys('RESTRICT'),
            fn (array $fk) => ! in_array($fk['table'], self::PRA_EXISTING_RESTRICT, true)
        ));

        $this->rebuild($fks, 'cascade');
    }

    /**
     * Perubahan foreign key hanya dilakukan pada MySQL/MariaDB.
     * SQLite (dipakai test) tidak memiliki information_schema.
     */
    private function supportsInformationSchema(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * Daftar foreign key pada tabel students untuk aturan ON DELETE tertentu.
     *
     * @return array<int, array{table: string, name: string, columns: array<int, string>}>
     */
    private function studentForeignKeys(string $deleteRule): array
    {
        $rows = DB::select(
            'select rc.constraint_name as constraint_name, rc.table_name as table_name
             from information_schema.referential_constraints rc
             where rc.constraint_schema = database()
               and rc.referenced_table_name = ?
               and rc.delete_rule = ?',
            ['students', $deleteRule]
        );

        $result = [];
        foreach ($rows as $row) {
            $columns = DB::select(
                'select column_name from information_schema.key_column_usage
                 where constraint_schema = database()
                   and table_name = ?
                   and constraint_name = ?
                 order by ordinal_position',
                [$row->table_name, $row->constraint_name]
            );

            if ($columns === []) {
                continue;
            }

            $result[] = [
                'table' => $row->table_name,
                'name' => $row->constraint_name,
                'columns' => array_map(fn ($column) => $column->column_name, $columns),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, array{table: string, name: string, columns: array<int, string>}>  $foreignKeys
     */
    private function rebuild(array $foreignKeys, string $onDelete): void
    {
        foreach ($foreignKeys as $fk) {
            Schema::table($fk['table'], function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk['name']);
            });

            Schema::table($fk['table'], function (Blueprint $table) use ($fk, $onDelete) {
                $foreign = $table->foreign($fk['columns'])->references('id')->on('students');

                if ($onDelete === 'cascade') {
                    $foreign->cascadeOnDelete();
                } else {
                    $foreign->restrictOnDelete();
                }
            });
        }
    }
};
