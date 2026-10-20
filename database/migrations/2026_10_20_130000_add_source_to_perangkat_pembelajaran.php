<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perangkat_pembelajaran')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                if (! Schema::hasColumn('perangkat_pembelajaran', 'source')) {
                    $table->string('source', 10)->default('manual')->after('tipe')
                        ->comment('manual / ai');
                }
                if (! Schema::hasColumn('perangkat_pembelajaran', 'ai_generated_content_id')) {
                    $table->uuid('ai_generated_content_id')->nullable()->after('source');
                    $table->index('ai_generated_content_id', 'perangkat_ai_content_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('perangkat_pembelajaran')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                if (Schema::hasColumn('perangkat_pembelajaran', 'ai_generated_content_id')) {
                    $table->dropIndex('perangkat_ai_content_idx');
                    $table->dropColumn('ai_generated_content_id');
                }
                if (Schema::hasColumn('perangkat_pembelajaran', 'source')) {
                    $table->dropColumn('source');
                }
            });
        }
    }
};
