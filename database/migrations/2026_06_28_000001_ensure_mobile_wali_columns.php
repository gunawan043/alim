<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure users table has all mobile/wali columns regardless of previous migration state
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id', 191)->nullable()->unique()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'no_kk')) {
                $table->string('no_kk', 30)->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'nik_wali')) {
                $table->string('nik_wali', 30)->nullable()->after('no_kk');
            }
            if (!Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('nik_wali');
            }
            if (!Schema::hasColumn('users', 'hubungan')) {
                $table->enum('hubungan', ['ayah', 'ibu', 'kakek', 'nenek', 'wali', 'lainnya'])
                    ->nullable()->after('no_hp');
            }
            if (!Schema::hasColumn('users', 'is_wali')) {
                $table->boolean('is_wali')->default(false)->after('hubungan');
            }
            if (!Schema::hasColumn('users', 'google_token')) {
                $table->text('google_token')->nullable()->after('is_wali');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['google_id', 'no_kk', 'nik_wali', 'no_hp', 'hubungan', 'is_wali', 'google_token'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
