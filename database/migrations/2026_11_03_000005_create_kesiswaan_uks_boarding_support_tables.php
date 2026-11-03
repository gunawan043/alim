<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pendukung untuk menu yang sebelumnya masih bertanda "Soon":
 *  - kesiswaan_duty_schedules   → Piket Kesiswaan
 *  - uks_student_insurances     → Asuransi / BPJS
 *  - dormitory_meal_consumptions → Konsumsi Asrama
 *  - dormitory_night_activities  → Pendampingan Kegiatan Malam
 *
 * Menu lain memakai tabel yang sudah ada:
 *  - Catat Keterlambatan / Keterlambatan → student_attendances (status=terlambat)
 *  - Piket Blok                          → dormitory_daily_tasks
 *  - Jadwal Latihan                      → ekstrakurikuler
 *  - Validasi Perangkat Ajar             → perangkat_pembelajaran
 *  - Riwayat Laporan Unit                → sarpras (laporan penugasan)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kesiswaan_duty_schedules')) {
            Schema::create('kesiswaan_duty_schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id')->nullable()->index();
                $table->uuid('academic_year_id')->nullable();
                $table->uuid('user_id')->index();
                $table->date('duty_date')->index();
                $table->string('shift', 20)->nullable();
                $table->string('location', 120)->nullable();
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('uks_student_insurances')) {
            Schema::create('uks_student_insurances', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('student_id')->index();
                $table->string('provider', 80);
                $table->string('card_number', 60)->nullable();
                $table->string('class_type', 30)->nullable();
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->string('status', 20)->default('aktif');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dormitory_meal_consumptions')) {
            Schema::create('dormitory_meal_consumptions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('dormitory_id')->index();
                $table->uuid('academic_year_id')->nullable();
                $table->date('consumption_date')->index();
                $table->string('meal_type', 20);
                $table->unsignedInteger('students_count')->default(0);
                $table->unsignedInteger('guests_count')->default(0);
                $table->text('notes')->nullable();
                $table->uuid('recorded_by')->nullable();
                $table->timestamps();

                $table->unique(['dormitory_id', 'consumption_date', 'meal_type'], 'meal_consumption_unique');
            });
        }

        if (! Schema::hasTable('dormitory_night_activities')) {
            Schema::create('dormitory_night_activities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('dormitory_id')->index();
                $table->uuid('academic_year_id')->nullable();
                $table->date('activity_date')->index();
                $table->string('name', 120);
                $table->string('start_time', 8);
                $table->string('end_time', 8)->nullable();
                $table->string('location', 120)->nullable();
                $table->string('person_in_charge', 120)->nullable();
                $table->unsignedInteger('student_count')->default(0);
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('rencana');
                $table->uuid('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_night_activities');
        Schema::dropIfExists('dormitory_meal_consumptions');
        Schema::dropIfExists('uks_student_insurances');
        Schema::dropIfExists('kesiswaan_duty_schedules');
    }
};
