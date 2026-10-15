<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat setiap pemindaian (satu gambar = satu baris).
 *
 * `image_hash` mencegah duplikasi pemindaian lembar yang sama; gambar
 * disimpan di disk privat dan hanya disajikan lewat route ber-otorisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_scans')) {
            return;
        }

        Schema::create('ljk_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ljk_exam_id')->nullable();
            $table->uuid('sheet_id')->nullable();
            $table->uuid('participant_id')->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->string('source', 12)->default('web');
            $table->string('image_path', 255);
            $table->char('image_hash', 64);
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->json('quality')->nullable();
            $table->string('status', 20)->default('received');
            $table->string('error_code', 40)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->string('engine_version', 20)->nullable();
            $table->unsignedInteger('processing_ms')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ljk_exam_id', 'ljk_scans_exam_fk')
                ->references('id')->on('ljk_exams')->cascadeOnDelete();
            $table->foreign('sheet_id', 'ljk_scans_sheet_fk')
                ->references('id')->on('ljk_sheets')->nullOnDelete();

            $table->index(['ljk_exam_id', 'status'], 'ljk_scans_exam_status_idx');
            $table->index('sheet_id', 'ljk_scans_sheet_idx');
            $table->index('image_hash', 'ljk_scans_image_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_scans');
    }
};
