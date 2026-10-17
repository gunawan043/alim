<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk menutup data gap dashboard:
     *  - vehicles            → Armada/Transport (Unit Rumah Tangga, Driver/Pengemudi)
     *  - guardian_complaints → Aduan Wali Santri (Personalia — Petugas Layanan Aduan)
     *  - spp_bills           → Tagihan/pembayaran SPP (Keuangan — Kasir SPP)
     */
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->string('plate_number', 20);
                $table->string('name', 100);
                $table->string('brand', 60)->nullable();
                $table->string('model', 60)->nullable();
                $table->string('type', 30)->default('mobil')->comment('mobil, motor, bus, truk, dll');
                $table->unsignedTinyInteger('capacity')->nullable();
                $table->string('status', 20)->default('tersedia')->comment('tersedia, digunakan, perawatan, nonaktif');
                $table->uuid('driver_id')->nullable();
                $table->date('last_service_date')->nullable();
                $table->date('next_service_date')->nullable();
                $table->unsignedInteger('odometer')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->index(['school_id', 'status']);
            });
        }

        if (! Schema::hasTable('guardian_complaints')) {
            Schema::create('guardian_complaints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('student_id')->nullable();
                $table->uuid('submitted_by')->nullable()->comment('user wali santri pengadu');
                $table->string('category', 50)->default('umum');
                $table->string('subject', 150);
                $table->text('message');
                $table->string('priority', 20)->default('normal')->comment('rendah, normal, tinggi');
                $table->string('status', 20)->default('baru')->comment('baru, diproses, selesai, ditolak');
                $table->text('response')->nullable();
                $table->uuid('responded_by')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->index(['school_id', 'status']);
            });
        }

        if (! Schema::hasTable('spp_bills')) {
            Schema::create('spp_bills', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('student_id');
                $table->uuid('academic_year_id')->nullable();
                $table->string('period', 7)->comment('YYYY-MM');
                $table->decimal('amount', 12, 2);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->string('status', 20)->default('belum_bayar')->comment('belum_bayar, sebagian, lunas');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->uuid('recorded_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->index(['school_id', 'period', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_bills');
        Schema::dropIfExists('guardian_complaints');
        Schema::dropIfExists('vehicles');
    }
};
