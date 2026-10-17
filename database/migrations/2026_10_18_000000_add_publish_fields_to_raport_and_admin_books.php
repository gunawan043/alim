<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('raport_registrations')) {
            Schema::table('raport_registrations', function (Blueprint $table) {
                if (! Schema::hasColumn('raport_registrations', 'is_published')) {
                    $table->boolean('is_published')->default(false)->after('status');
                }
                if (! Schema::hasColumn('raport_registrations', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('finalized_at');
                }
                if (! Schema::hasColumn('raport_registrations', 'published_by')) {
                    $table->foreignUuid('published_by')->nullable()->after('published_at')
                        ->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('teacher_admin_books')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                if (! Schema::hasColumn('teacher_admin_books', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('locked_at');
                }
                if (! Schema::hasColumn('teacher_admin_books', 'published_by')) {
                    $table->foreignUuid('published_by')->nullable()->after('published_at')
                        ->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('raport_registrations')) {
            Schema::table('raport_registrations', function (Blueprint $table) {
                if (Schema::hasColumn('raport_registrations', 'published_by')) {
                    $table->dropConstrainedForeignId('published_by');
                }
                foreach (['is_published', 'published_at'] as $col) {
                    if (Schema::hasColumn('raport_registrations', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('teacher_admin_books')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_admin_books', 'published_by')) {
                    $table->dropConstrainedForeignId('published_by');
                }
                if (Schema::hasColumn('teacher_admin_books', 'published_at')) {
                    $table->dropColumn('published_at');
                }
            });
        }
    }
};
