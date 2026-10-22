<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('password_otps')) {
            return;
        }

        Schema::table('password_otps', function (Blueprint $table) {
            if (! Schema::hasColumn('password_otps', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('otp_hash');
            }
            if (! Schema::hasColumn('password_otps', 'used_at')) {
                $table->timestamp('used_at')->nullable()->after('attempts');
            }
            if (! Schema::hasColumn('password_otps', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable()->after('used_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('password_otps')) {
            Schema::table('password_otps', function (Blueprint $table) {
                $table->dropColumn(['attempts', 'used_at', 'last_sent_at']);
            });
        }
    }
};
