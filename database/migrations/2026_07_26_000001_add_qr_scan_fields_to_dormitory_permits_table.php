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
            $table->enum('pickup_mode', ['manual', 'web', 'public_qr'])->default('manual')->after('last_actioned_by');
            $table->string('return_by', 100)->nullable()->after('pickup_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->dropColumn(['pickup_mode', 'return_by']);
        });
    }
};
