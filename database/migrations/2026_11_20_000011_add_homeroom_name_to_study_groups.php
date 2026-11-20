<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_groups')) {
            return;
        }

        Schema::table('study_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('study_groups', 'homeroom_name')) {
                $table->string('homeroom_name', 150)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('study_groups')) {
            return;
        }

        Schema::table('study_groups', function (Blueprint $table) {
            if (Schema::hasColumn('study_groups', 'homeroom_name')) {
                $table->dropColumn('homeroom_name');
            }
        });
    }
};
