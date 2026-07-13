<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_scan_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_id');
            $table->unsignedBigInteger('scanned_by')->nullable();
            $table->string('scan_type')->default('qr'); // qr, manual_code
            $table->string('lookup_value'); // the QR token or asset_code
            $table->string('source')->nullable(); // url, mobile_app, web, scanner
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('purpose')->nullable(); // view, audit, report_damage, etc
            $table->timestamps();

            $table->index('asset_id');
            $table->index('scanned_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scan_histories');
    }
};
