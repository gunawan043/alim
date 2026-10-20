<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisi', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisi', 'supervisi_sk_id')) {
                $table->foreignUuid('supervisi_sk_id')->nullable()->after('school_id')
                    ->constrained('supervisi_sk')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supervisi', function (Blueprint $table) {
            if (Schema::hasColumn('supervisi', 'supervisi_sk_id')) {
                $table->dropConstrainedForeignId('supervisi_sk_id');
            }
        });
    }
};
