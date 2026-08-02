<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system_admin')->default(false)->after('is_active');
            $table->boolean('is_permanent')->default(false)->after('is_system_admin');
            $table->string('username')->nullable()->unique()->after('email');

            $table->index('is_system_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['is_system_admin']);
            $table->dropColumn(['is_system_admin', 'is_permanent', 'username']);
        });
    }
};
