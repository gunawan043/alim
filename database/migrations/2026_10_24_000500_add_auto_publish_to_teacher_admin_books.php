<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_admin_books')
            && ! Schema::hasColumn('teacher_admin_books', 'auto_publish')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->boolean('auto_publish')->default(true)->after('published_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_admin_books')
            && Schema::hasColumn('teacher_admin_books', 'auto_publish')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->dropColumn('auto_publish');
            });
        }
    }
};
