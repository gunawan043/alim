<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curriculum_profiles')) {
            Schema::create('curriculum_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('authority', 30)->default('dinas')
                    ->comment('kemenag / dinas');
                $table->string('version', 50)->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->json('config')->nullable()
                    ->comment('Label, section, nilai, dan template cetak per profil');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['authority', 'is_active']);
            });
        }

        if (! Schema::hasTable('school_curriculum_settings')) {
            Schema::create('school_curriculum_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignUuid('curriculum_profile_id')->nullable()
                    ->constrained('curriculum_profiles')->nullOnDelete();
                $table->string('kokurikuler_type', 30)->nullable()
                    ->comment('p5 / p5ra / lainnya');
                $table->boolean('is_locked')->default(false)
                    ->comment('Kunci profil agar dokumen periode berjalan tidak berpindah');
                $table->text('notes')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'academic_year_id'], 'school_curriculum_unique');
                $table->index(['curriculum_profile_id', 'academic_year_id']);
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_curriculum_settings');
        Schema::dropIfExists('curriculum_profiles');
    }
};
