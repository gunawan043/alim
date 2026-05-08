<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Create recruitment_pipelines
        |--------------------------------------------------------------------------
        */
        Schema::create('recruitment_pipelines', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('recruitment_job_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('nama_tahapan');
            $table->integer('urutan');
            $table->text('deskripsi')->nullable();
            $table->integer('durasi_hari')->default(1);
            $table->string('warna')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignUuid('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Short index name
            $table->index(['recruitment_job_id', 'urutan'], 'pipe_job_urutan_idx');
        });


        /*
        |--------------------------------------------------------------------------
        | 2. Create recruitment_pipeline_stages
        |--------------------------------------------------------------------------
        */
        Schema::create('recruitment_pipeline_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('recruitment_pipeline_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('nama_tahapan');
            $table->integer('urutan');
            $table->text('deskripsi')->nullable();
            $table->integer('durasi_hari')->default(1);
            $table->string('warna')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_wajib')->default(true);

            $table->json('kriteria_kelulusan')->nullable();
            $table->json('form_penilaian')->nullable();
            $table->json('notification_template')->nullable();
            $table->json('email_template')->nullable();

            $table->timestamps();

            // Short index name
            $table->index(['recruitment_pipeline_id', 'urutan'], 'stage_pipe_urutan_idx');
        });


        /*
        |--------------------------------------------------------------------------
        | 3. Add current_stage_id AFTER pipeline_stages exists
        |--------------------------------------------------------------------------
        */
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->uuid('current_stage_id')->nullable();

            $table->foreign('current_stage_id', 'fk_app_current_stage')
                ->references('id')
                ->on('recruitment_pipeline_stages')
                ->nullOnDelete();
        });


        /*
        |--------------------------------------------------------------------------
        | 4. Create recruitment_application_stages
        |--------------------------------------------------------------------------
        */
        Schema::create('recruitment_application_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('recruitment_application_id');
            $table->uuid('recruitment_pipeline_stage_id');

            $table->integer('urutan');

            $table->enum('status', [
                'menunggu',
                'sedang_berlangsung',
                'lolos',
                'tidak_lolos',
                'ulang'
            ])->default('menunggu');

            $table->dateTime('jadwal_mulai')->nullable();
            $table->dateTime('jadwal_selesai')->nullable();
            $table->string('lokasi')->nullable();

            $table->foreignUuid('penilai_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('tim_penilai')->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->json('detail_penilaian')->nullable();
            $table->string('hasil_path')->nullable();
            $table->dateTime('dimulai_at')->nullable();
            $table->dateTime('selesai_at')->nullable();

            $table->timestamps();

            // SHORT FK NAMES
            $table->foreign('recruitment_application_id', 'fk_app_stage_app')
                ->references('id')
                ->on('recruitment_applications')
                ->cascadeOnDelete();

            $table->foreign('recruitment_pipeline_stage_id', 'fk_app_stage_pipeline')
                ->references('id')
                ->on('recruitment_pipeline_stages')
                ->cascadeOnDelete();

            // SHORT INDEX NAMES - FIXED THE ISSUE HERE
            $table->index(['recruitment_application_id', 'urutan'], 'app_stage_app_urut_idx');
            $table->index('status', 'app_stage_status_idx');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('recruitment_application_stages');

        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropForeign('fk_app_current_stage');
            $table->dropColumn('current_stage_id');
        });

        Schema::dropIfExists('recruitment_pipeline_stages');
        Schema::dropIfExists('recruitment_pipelines');
    }
};