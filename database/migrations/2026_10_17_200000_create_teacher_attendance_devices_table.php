<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opsi B (QR cetak tetap) — kunci perangkat opsional:
 * satu perangkat terdaftar per GTK untuk mencegah titip absen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_attendance_devices')) {
            Schema::create('teacher_attendance_devices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('device_hash', 64);
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->unique('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendance_devices');
    }
};
