<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_scan_histories')) {
            return;
        }

        if (DB::table('qr_scan_histories')->exists()) {
            return;
        }

        Schema::dropIfExists('qr_scan_histories');

        Schema::create('qr_scan_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->uuid('asset_id')->nullable();
            $table->uuid('scanned_by')->nullable();
            $table->string('scan_type', 30)->default('qr');
            $table->string('lookup_value');
            $table->string('source', 50)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('purpose')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
            $table->foreign('scanned_by')->references('id')->on('users')->nullOnDelete();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        // Rebuild lama tidak dikembalikan karena skema lamanya tidak kompatibel dengan UUID.
    }
};
