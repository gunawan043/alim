<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisi_sk', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisi_sk', 'institution_decree_id')) {
                $table->foreignUuid('institution_decree_id')->nullable()->after('semester')
                    ->constrained('institution_decrees')->nullOnDelete();
            }
            if (! Schema::hasColumn('supervisi_sk', 'surat_keluar_id')) {
                $table->foreignUuid('surat_keluar_id')->nullable()->after('institution_decree_id')
                    ->constrained('surat_keluar')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supervisi_sk', function (Blueprint $table) {
            if (Schema::hasColumn('supervisi_sk', 'surat_keluar_id')) {
                $table->dropConstrainedForeignId('surat_keluar_id');
            }
            if (Schema::hasColumn('supervisi_sk', 'institution_decree_id')) {
                $table->dropConstrainedForeignId('institution_decree_id');
            }
        });
    }
};
