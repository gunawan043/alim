<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_photos', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_photos', 'context_type')) {
                $table->string('context_type', 100)->nullable()->after('asset_id');
            }
            if (! Schema::hasColumn('asset_photos', 'context_id')) {
                $table->string('context_id', 36)->nullable()->after('context_type');
            }
            if (! Schema::hasColumn('asset_photos', 'photo_type')) {
                $table->enum('photo_type', ['before', 'after', 'documentation', 'damage', 'audit', 'profile', 'sparepart', 'other'])
                    ->default('documentation')->after('caption');
            }
            if (! Schema::hasColumn('asset_photos', 'taken_at')) {
                $table->timestamp('taken_at')->nullable()->after('photo_type');
            }
            if (! Schema::hasColumn('asset_photos', 'metadata')) {
                $table->json('metadata')->nullable()->after('uploaded_by');
            }
        });

        Schema::table('asset_photos', function (Blueprint $table) {
            $table->index(['context_type', 'context_id']);
            $table->index(['asset_id', 'photo_type']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_photos', function (Blueprint $table) {
            $table->dropIndex(['context_type', 'context_id']);
            $table->dropIndex(['asset_id', 'photo_type']);
            $table->dropColumn(['context_type', 'context_id', 'photo_type', 'taken_at', 'uploaded_by', 'metadata']);
        });
    }
};
