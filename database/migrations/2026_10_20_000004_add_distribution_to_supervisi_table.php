<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisi', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisi', 'distributed_at')) {
                $table->timestamp('distributed_at')->nullable()->after('reported_to_wadir_at');
            }
            if (! Schema::hasColumn('supervisi', 'distributed_by')) {
                $table->foreignUuid('distributed_by')->nullable()->after('distributed_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supervisi', function (Blueprint $table) {
            if (Schema::hasColumn('supervisi', 'distributed_by')) {
                $table->dropConstrainedForeignId('distributed_by');
            }
            if (Schema::hasColumn('supervisi', 'distributed_at')) {
                $table->dropColumn('distributed_at');
            }
        });
    }
};
