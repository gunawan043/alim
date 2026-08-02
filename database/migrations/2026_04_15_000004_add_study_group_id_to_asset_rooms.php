<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->uuid('study_group_id')->nullable()->after('responsible_user_id');
            $table->foreign('study_group_id')
                ->references('id')
                ->on('study_groups')
                ->nullOnDelete();
            $table->index('study_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->dropForeign(['study_group_id']);
            $table->dropColumn('study_group_id');
        });
    }
};
