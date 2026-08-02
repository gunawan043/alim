<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            // Explicit emergency override flag (separate from special permission)
            $table->boolean('is_emergency')->default(false)
                ->after('is_special_permission')
                ->comment('True when this permit is submitted as an emergency request.');

            // Who is the emergency contact besides mahrom?
            $table->string('emergency_contact_name')->nullable()->after('is_emergency');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');

            // Pickup scenario tracking
            $table->json('pickup_details')->nullable()
                ->after('actual_return_datetime')
                ->comment('Stored JSON: { mode: "jemput|manual"|null, scanned_by?: string, confirmed_at?: string }');

            // QR scan tracking
            $table->string('scan_token')->nullable()->unique()->after('created_at')
                ->comment('Signed UUID used for QR code verification during pickup/return scans.');
            $table->timestamp('scanned_at')->nullable()->after('scan_token');

            // Tracking who did the last action (pickup / return scan)
            $table->uuid('last_actioned_by')->nullable()->after('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->dropColumn([
                'is_emergency',
                'emergency_contact_name',
                'emergency_contact_phone',
                'pickup_details',
                'scan_token',
                'scanned_at',
                'last_actioned_by',
            ]);
        });
    }
};
