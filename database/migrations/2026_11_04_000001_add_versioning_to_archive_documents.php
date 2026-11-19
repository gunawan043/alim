<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('archive_documents')) {
            return;
        }

        Schema::table('archive_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('archive_documents', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('file_size');
            }
            if (! Schema::hasColumn('archive_documents', 'supersedes_id')) {
                $table->uuid('supersedes_id')->nullable()->after('version');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('archive_documents')) {
            return;
        }

        Schema::table('archive_documents', function (Blueprint $table) {
            foreach (['supersedes_id', 'version'] as $column) {
                if (Schema::hasColumn('archive_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
