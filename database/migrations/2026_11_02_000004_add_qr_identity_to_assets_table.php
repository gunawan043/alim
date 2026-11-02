<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'qr_mode')) {
                $table->string('qr_mode', 20)->default('individual')
                    ->after('qr_generated_at')
                    ->comment('individual = QR per unit, group = QR seragam per kelompok');
            }

            if (! Schema::hasColumn('assets', 'qr_group_key')) {
                $table->string('qr_group_key', 50)->nullable()->after('qr_mode');
                $table->index('qr_group_key', 'assets_qr_group_key_index');
            }
        });

        // QR dibuat otomatis: aset lama langsung dianggap sudah punya QR.
        DB::table('assets')
            ->whereNull('qr_generated_at')
            ->whereNull('deleted_at')
            ->update(['qr_generated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_qr_group_key_index');
            $table->dropColumn(['qr_mode', 'qr_group_key']);
        });
    }
};
