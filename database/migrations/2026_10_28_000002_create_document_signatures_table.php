<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry dokumen resmi yang diterbitkan ALIM.
 *
 * Satu baris = satu dokumen final (bukan template). Token QR hanya disimpan
 * dalam bentuk hash; token asli tidak pernah disimpan di database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_signatures')) {
            Schema::create('document_signatures', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('document_type', 60);
                $table->string('document_number', 120)->nullable();
                $table->string('title');
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->enum('semester', ['ganjil', 'genap'])->nullable();
                $table->string('subject_type', 60)->nullable();
                $table->uuid('subject_id')->nullable();
                $table->string('validation_mode', 20)->default('qr');
                $table->string('status', 20)->default('issued');
                $table->longText('canonical_payload');
                $table->char('payload_hash', 64);
                $table->char('file_hash', 64)->nullable();
                $table->char('verification_token_hash', 64)->unique();
                $table->text('signature')->nullable();
                $table->string('pades_profile', 30)->nullable();
                $table->foreignUuid('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('issued_at');
                $table->timestamp('revoked_at')->nullable();
                $table->text('revocation_reason')->nullable();
                $table->unsignedInteger('scan_count')->default(0);
                $table->timestamp('last_scanned_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['document_type', 'status'], 'doc_sig_type_status_idx');
                $table->index(['school_id', 'academic_year_id'], 'doc_sig_school_ay_idx');
                $table->index(['subject_type', 'subject_id'], 'doc_sig_subject_idx');
            });
        }

        if (! Schema::hasTable('document_signature_signers')) {
            Schema::create('document_signature_signers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('document_signature_id')->constrained('document_signatures')->cascadeOnDelete();
                $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('role', 120)->nullable();
                $table->unsignedTinyInteger('signer_order')->default(1);
                $table->string('certificate_subject')->nullable();
                $table->string('certificate_serial')->nullable();
                $table->timestamp('certificate_expires_at')->nullable();
                $table->string('signature_status', 20)->default('visual');
                $table->string('pades_profile', 30)->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['document_signature_id', 'signer_order'], 'doc_signer_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signature_signers');
        Schema::dropIfExists('document_signatures');
    }
};
