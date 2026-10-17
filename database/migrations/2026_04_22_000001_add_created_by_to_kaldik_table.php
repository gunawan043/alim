<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kaldik', 'created_by')) {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->foreignUuid('created_by')->nullable()->after('work_unit_id')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kaldik', 'created_by')) {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
