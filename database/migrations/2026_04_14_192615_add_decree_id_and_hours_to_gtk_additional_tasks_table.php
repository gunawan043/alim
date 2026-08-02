<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->foreignUuid('decree_id')
                ->nullable()
                ->after('user_id')
                ->constrained('institution_decrees')
                ->nullOnDelete();

            $table->unsignedTinyInteger('hours_per_week')
                ->nullable()
                ->after('nama_tugas');
        });
    }

    public function down(): void
    {
        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->dropForeign(['decree_id']);
            $table->dropColumn(['decree_id', 'hours_per_week']);
        });
    }
};
