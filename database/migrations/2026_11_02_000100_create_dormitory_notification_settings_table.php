<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan pengingat otomatis santri yang belum kembali ke asrama.
 * Satu baris per asrama; bila belum ada, nilai default dipakai (lihat model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id')->unique();

            $table->boolean('is_enabled')->default(true);
            $table->boolean('h1_reminder_enabled')->default(true);
            $table->string('h1_reminder_time', 5)->default('16:00');

            // Interval pengingat berulang selama santri belum kembali (menit).
            $table->unsignedInteger('interval_minutes')->default(30);
            $table->unsignedInteger('max_reminders')->default(3);
            // Eskalasi ke petugas setelah sekian menit dari batas kembali.
            $table->unsignedInteger('escalate_after_minutes')->default(180);
            $table->boolean('notify_staff')->default(true);

            // Kanal: whatsapp, push, email, in_app.
            $table->json('channels')->nullable();
            $table->text('message_template')->nullable();

            $table->timestamps();

            $table->foreign('dormitory_id')
                ->references('id')
                ->on('dormitories')
                ->cascadeOnDelete();
        });

        Schema::table('dormitory_permits', function (Blueprint $table) {
            if (! Schema::hasColumn('dormitory_permits', 'return_reminder_sent_at')) {
                $table->timestamp('return_reminder_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            if (Schema::hasColumn('dormitory_permits', 'return_reminder_sent_at')) {
                $table->dropColumn('return_reminder_sent_at');
            }
        });

        Schema::dropIfExists('dormitory_notification_settings');
    }
};
