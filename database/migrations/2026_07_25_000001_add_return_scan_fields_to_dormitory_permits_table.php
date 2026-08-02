<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            // Pickup tracking (separate from generic scanned_at)
            if (! Schema::hasColumn('dormitory_permits', 'pickup_scanned_at')) {
                $table->timestamp('pickup_scanned_at')->nullable()->after('scanned_at');
            }
            if (! Schema::hasColumn('dormitory_permits', 'pickup_scanned_by')) {
                $table->string('pickup_scanned_by', 36)->nullable()->after('pickup_scanned_at');
            }

            // Return/arrival tracking via QR scan
            if (! Schema::hasColumn('dormitory_permits', 'return_scanned_at')) {
                $table->timestamp('return_scanned_at')->nullable()->after('actual_return_datetime');
            }
            if (! Schema::hasColumn('dormitory_permits', 'return_scanned_by')) {
                $table->string('return_scanned_by', 36)->nullable()->after('return_scanned_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_scanned_at',
                'pickup_scanned_by',
                'return_scanned_at',
                'return_scanned_by',
            ]);
        });
    }
};
