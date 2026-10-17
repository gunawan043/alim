<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_mutations_out', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();

            // Status persetujuan
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');

            // ── Kop Surat ─────────────────────────────────────────────
            $table->string('institution_name', 255)->nullable();
            $table->string('institution_address', 500)->nullable();
            $table->string('institution_phone', 50)->nullable();
            $table->string('institution_email', 100)->nullable();
            // Penanda tangan
            $table->string('head_name', 100)->nullable();
            $table->string('head_title', 100)->nullable();
            $table->string('head_nip', 30)->nullable();

            // ── Nomor Surat ───────────────────────────────────────────
            $table->string('letter_number', 50)->nullable();
            $table->string('letter_attachment', 20)->nullable();
            $table->string('established_city', 100)->nullable();
            $table->date('established_date')->nullable();

            // ── Data Santri ───────────────────────────────────────────
            $table->string('student_nisn', 20)->nullable();
            $table->string('student_nis', 20)->nullable();
            $table->string('student_name', 255);
            $table->enum('student_gender', ['L', 'P'])->nullable();
            $table->date('student_birth_date')->nullable();
            $table->string('student_birth_place', 100)->nullable();
            $table->string('student_address', 500)->nullable();
            $table->string('student_previous_school', 255)->nullable();
            $table->string('student_current_class', 50)->nullable();

            // ── Data Orang Tua/Wali ──────────────────────────────────
            $table->string('parent_name', 255)->nullable();
            $table->string('parent_occupation', 100)->nullable();
            $table->string('parent_address', 500)->nullable();
            $table->string('parent_phone', 30)->nullable();

            // ── Sekolah Tujuan ────────────────────────────────────────
            $table->string('destination_school_name', 255)->nullable();
            $table->string('destination_school_address', 500)->nullable();
            $table->string('destination_school_city', 100)->nullable();

            // ── Alasan ────────────────────────────────────────────────
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // ── Approval ──────────────────────────────────────────────
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->index('student_id');
            $table->index('status');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_mutations_out');
    }
};
