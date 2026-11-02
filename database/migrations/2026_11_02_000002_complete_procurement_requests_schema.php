<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Model ProcurementRequest, form pengajuan, dan halaman pengadaan sudah
 * memakai kolom purpose, budget_source, delivery_date, dsb. — tetapi tabel
 * yang dibuat migrasi lama belum memilikinya sehingga pengajuan pengadaan
 * gagal disimpan (SQLSTATE 42S22) dan agenda pengadaan selalu kosong.
 *
 * Migrasi ini melengkapi kolom tersebut secara idempoten.
 */
return new class extends Migration
{
    private array $columns = [
        'purpose' => ['type' => 'text', 'nullable' => true],
        'budget_source' => ['type' => 'string', 'length' => 100, 'nullable' => true],
        'total_estimated_budget' => ['type' => 'decimal', 'nullable' => true],
        'procurement_method' => ['type' => 'string', 'length' => 50, 'nullable' => true],
        'vendor_name' => ['type' => 'string', 'length' => 191, 'nullable' => true],
        'purchase_order_number' => ['type' => 'string', 'length' => 100, 'nullable' => true],
        'purchase_order_date' => ['type' => 'date', 'nullable' => true],
        'delivery_date' => ['type' => 'date', 'nullable' => true],
        'received_by' => ['type' => 'uuid', 'nullable' => true],
        'received_date' => ['type' => 'date', 'nullable' => true],
        'total_actual_cost' => ['type' => 'decimal', 'nullable' => true],
        'payment_status' => ['type' => 'string', 'length' => 30, 'nullable' => true],
        'created_by' => ['type' => 'uuid', 'nullable' => true],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('procurement_requests')) {
            return;
        }

        Schema::table('procurement_requests', function (Blueprint $table) {
            foreach ($this->columns as $name => $definition) {
                if (Schema::hasColumn('procurement_requests', $name)) {
                    continue;
                }

                $column = match ($definition['type']) {
                    'text' => $table->text($name),
                    'decimal' => $table->decimal($name, 15, 2),
                    'date' => $table->date($name),
                    'uuid' => $table->uuid($name),
                    default => $table->string($name, $definition['length'] ?? 191),
                };

                if ($definition['nullable'] ?? false) {
                    $column->nullable();
                }
            }
        });

        $this->widenEnums();
    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_requests')) {
            return;
        }

        Schema::table('procurement_requests', function (Blueprint $table) {
            foreach (array_keys($this->columns) as $name) {
                if (Schema::hasColumn('procurement_requests', $name)) {
                    $table->dropColumn($name);
                }
            }
        });
    }

    /**
     * Enum status/urgency di tabel lama tidak memuat nilai yang dipakai
     * form ('pending', 'normal'). Perluas hanya pada MySQL — SQLite
     * (dipakai test) menyimpan enum sebagai varchar dengan CHECK.
     */
    private function widenEnums(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE procurement_requests MODIFY status ENUM(
            'draft','pending','submitted','review','approved','rejected',
            'ordered','purchasing','delivered','received','completed','cancelled'
        ) NOT NULL DEFAULT 'draft'");

        DB::statement("ALTER TABLE procurement_requests MODIFY urgency ENUM(
            'rendah','normal','sedang','tinggi','mendesak'
        ) NOT NULL DEFAULT 'sedang'");
    }
};
