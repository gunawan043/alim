<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
            if (! Schema::hasColumn('perangkat_pembelajaran', 'ksp_approved_at')) {
                $table->timestamp('ksp_approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('perangkat_pembelajaran', 'ksp_approved_by')) {
                $table->uuid('ksp_approved_by')->nullable()->after('ksp_approved_at');
            }
            if (! Schema::hasColumn('perangkat_pembelajaran', 'ksp_note')) {
                $table->text('ksp_note')->nullable()->after('ksp_approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
            foreach (['ksp_note', 'ksp_approved_by', 'ksp_approved_at'] as $column) {
                if (Schema::hasColumn('perangkat_pembelajaran', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
