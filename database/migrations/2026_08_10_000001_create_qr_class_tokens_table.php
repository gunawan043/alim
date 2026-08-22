<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_class_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('study_group_id')->index();
            $table->uuid('school_id')->index();
            $table->uuid('academic_year_id')->nullable()->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('qr_url_expires_at')->nullable()
                ->comment('Temporary-signed URL expiry; NULL = permanent QR');
            $table->timestamp('last_regenerated_at')->nullable();
            $table->integer('scan_count')->default(0);
            $table->timestamp('last_scan_at')->nullable();
            $table->timestamps();

            $table->foreign('study_group_id')
                ->references('id')->on('study_groups')
                ->cascadeOnDelete();
            $table->foreign('school_id')
                ->references('id')->on('schools')
                ->cascadeOnDelete();
            $table->foreign('academic_year_id')
                ->references('id')->on('academic_years')
                ->nullOnDelete();

            // Satu study group = satu token aktif per tahun ajaran
            $table->unique(
                ['study_group_id', 'academic_year_id'],
                'uniq_qct_sg_ay'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_class_tokens');
    }
};
