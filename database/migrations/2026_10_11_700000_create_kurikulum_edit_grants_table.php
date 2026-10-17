<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan & pemberian akses edit dokumen kurikulum per (sekolah, mapel, tahun ajaran).
 * Kebijakan "keroyokan terkendali": guru serumpun harus mengajukan izin edit;
 * hanya akun yang disetujui yang dapat mengelola dokumen — sisanya lihat + salin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kurikulum_edit_grants')) {
            Schema::create('kurikulum_edit_grants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('subject_id');
                $table->uuid('academic_year_id');
                $table->uuid('requested_by');
                $table->string('status', 20)->default('pending'); // pending|approved|rejected|revoked
                $table->text('reason')->nullable();
                $table->uuid('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->text('decision_note')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'subject_id', 'academic_year_id', 'status'], 'keg_context_status_idx');
                $table->index(['requested_by', 'status'], 'keg_requester_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kurikulum_edit_grants');
    }
};
