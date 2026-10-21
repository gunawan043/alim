<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cuti_requests') && ! Schema::hasColumn('cuti_requests', 'approval_stage')) {
            Schema::table('cuti_requests', function (Blueprint $table) {
                $table->unsignedTinyInteger('approval_stage')->default(0)->after('approval_chain');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cuti_requests') && Schema::hasColumn('cuti_requests', 'approval_stage')) {
            Schema::table('cuti_requests', fn (Blueprint $table) => $table->dropColumn('approval_stage'));
        }
    }
};
