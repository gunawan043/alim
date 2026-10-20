<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_titles') && ! Schema::hasColumn('library_titles', 'collection_type')) {
            Schema::table('library_titles', function (Blueprint $table) {
                $table->string('collection_type', 30)->default('umum')->after('genre_id');
                $table->index('collection_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('library_titles') && Schema::hasColumn('library_titles', 'collection_type')) {
            Schema::table('library_titles', fn (Blueprint $table) => $table->dropColumn('collection_type'));
        }
    }
};
