<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_admin_books')
            && ! Schema::hasColumn('teacher_admin_books', 'guardian_visible_columns')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->json('guardian_visible_columns')->nullable()->after('published_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_admin_books')
            && Schema::hasColumn('teacher_admin_books', 'guardian_visible_columns')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->dropColumn('guardian_visible_columns');
            });
        }
    }
};
