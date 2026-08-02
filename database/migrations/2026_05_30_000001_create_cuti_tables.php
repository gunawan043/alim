<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuti_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');          // Cuti Tahunan, Cuti Sakit, Cuti Besar, etc.
            $table->string('jenis');         // TAHUNAN, SAKIT, BESAR, LAINNYA
            $table->integer('jumlah_hari');  // jumlah hari per tahun
            $table->boolean('paid');         // apakah berbayar
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('cuti_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');          // e.g. "2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cuti_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('cuti_template_id')->constrained('cuti_templates')->cascadeOnDelete();
            $table->foreignUuid('cuti_period_id')->nullable()->constrained('cuti_periods')->nullOnDelete();
            $table->integer('jumlah_hari');     // jatah awal
            $table->integer('digunakan');      // sudah dipakai
            $table->integer('tersisa');        // auto: jatah - digunakan
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'cuti_template_id', 'cuti_period_id'], 'cuti_balance_unique');
        });

        Schema::create('cuti_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('cuti_template_id')->constrained('cuti_templates')->cascadeOnDelete();
            $table->foreignUuid('cuti_period_id')->nullable()->constrained('cuti_periods')->nullOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('jumlah_hari');        // total hari cuti
            $table->text('alasan')->nullable();
            $table->string('status');              // PENDING, APPROVED, REJECTED, CANCELLED
            $table->string('attachment')->nullable(); // path ke file
            $table->json('approval_chain')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignUuid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuti_requests');
        Schema::dropIfExists('cuti_balances');
        Schema::dropIfExists('cuti_periods');
        Schema::dropIfExists('cuti_templates');
    }
};
