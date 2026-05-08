<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->foreignUuid('school_id')
                  ->nullable()
                  ->constrained('schools')
                  ->cascadeOnDelete();

            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });
    }
};
