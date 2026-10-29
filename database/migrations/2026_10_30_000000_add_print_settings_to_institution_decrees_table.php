<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('institution_decrees', 'print_settings')) {
            return;
        }

        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->json('print_settings')->nullable()->after('matrix_snapshot_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('institution_decrees', 'print_settings')) {
            return;
        }

        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->dropColumn('print_settings');
        });
    }
};
