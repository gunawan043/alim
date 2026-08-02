<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->after('email');
            $table->string('portal_token', 64)->unique()->nullable()->after('password');
            $table->timestamp('last_portal_login')->nullable()->after('portal_token');
        });

        // Seed initial portal passwords for active vendors without one
        DB::table('vendors')
            ->whereNull('password')
            ->where('status', 'active')
            ->update([
                'password' => Hash::make(Str::random(16)),
                'portal_token' => Str::random(32),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->change();
            $table->dropColumn(['password', 'portal_token', 'last_portal_login']);
        });
    }
};
