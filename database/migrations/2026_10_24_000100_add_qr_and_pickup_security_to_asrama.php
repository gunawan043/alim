<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->timestamp('scan_token_expires_at')->nullable()->after('scan_token');
            $table->timestamp('scan_token_revoked_at')->nullable()->after('scan_token_expires_at');

            // null = legacy card/mahrom flow; pending = menunggu verifikasi
            // kepala/wakil asrama; approved/rejected = hasil verifikasi.
            $table->string('pickup_authorization_status', 20)->nullable()->after('pickup_details');
            $table->text('pickup_authorization_note')->nullable()->after('pickup_authorization_status');
            $table->uuid('pickup_authorized_by')->nullable()->after('pickup_authorization_note');
            $table->timestamp('pickup_authorized_at')->nullable()->after('pickup_authorized_by');
        });

        Schema::create('dormitory_qr_scan_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id')->index();
            $table->uuid('permit_id')->nullable()->index();
            $table->uuid('visit_id')->nullable()->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('action', 30);
            $table->string('result', 20);
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['dormitory_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_qr_scan_logs');

        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->dropColumn([
                'scan_token_expires_at',
                'scan_token_revoked_at',
                'pickup_authorization_status',
                'pickup_authorization_note',
                'pickup_authorized_by',
                'pickup_authorized_at',
            ]);
        });
    }
};
