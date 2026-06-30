<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gtk_id')->nullable()->constrained('gtk_profiles')->nullOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('gaji_pokok', 14, 2)->default(0);
            $table->decimal('total_tunjangan', 14, 2)->default(0);
            $table->decimal('total_potongan', 14, 2)->default(0);
            $table->decimal('gaji_bersih', 14, 2)->default(0);
            $table->json('detail_tunjangan')->nullable();
            $table->json('detail_potongan')->nullable();
            $table->string('status', 30)->default('draft');
            $table->date('tanggal_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->unique(['gtk_id', 'bulan', 'tahun'], 'payroll_gtk_period_unique');
            $table->index(['tahun', 'bulan']);
            $table->index('status');
        });

        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
        Schema::dropIfExists('payroll');
    }
};