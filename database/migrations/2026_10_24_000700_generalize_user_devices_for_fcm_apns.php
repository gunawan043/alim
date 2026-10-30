<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generalisasi perangkat push: dari subscription OneSignal menjadi token
 * native FCM (Android) dan APNs (iOS).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_devices')) {
            return;
        }

        Schema::table('user_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('user_devices', 'provider')) {
                $table->string('provider', 20)->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('user_devices', 'push_token')) {
                $table->string('push_token', 512)->nullable()->after('provider');
            }
        });

        // Subscription OneSignal bukan token FCM/APNs. Revoke baris lama agar
        // backend tidak mencoba mengirim ke token yang tidak valid; aplikasi
        // akan mendaftar ulang saat dibuka setelah update.
        if (Schema::hasColumn('user_devices', 'onesignal_subscription_id')) {
            DB::table('user_devices')->update([
                'revoked_at' => now(),
            ]);

            try {
                Schema::table('user_devices', function (Blueprint $table) {
                    $table->dropUnique('user_devices_onesignal_subscription_id_unique');
                });
            } catch (Throwable) {
                // Nama index dapat berbeda pada database lama.
            }

            Schema::table('user_devices', function (Blueprint $table) {
                $table->dropColumn('onesignal_subscription_id');
            });
        }

        Schema::table('user_devices', function (Blueprint $table) {
            $table->index(['provider', 'revoked_at']);
        });

        try {
            Schema::table('user_devices', function (Blueprint $table) {
                $table->unique('push_token');
            });
        } catch (Throwable) {
            // Unique index mungkin sudah ada pada deployment yang diulang.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_devices')) {
            return;
        }

        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropUnique('user_devices_push_token_unique');
            $table->dropIndex('user_devices_provider_revoked_at_index');
        });

        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropColumn(['provider', 'push_token']);
        });
    }
};
