<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('letter_number_sequences')) {
            Schema::create('letter_number_sequences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
                $table->string('type', 20); // masuk | keluar
                $table->unsignedSmallInteger('year');
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();

                $table->unique(['school_id', 'type', 'year']);
            });
        }

        if (! Schema::hasTable('surat_templates')) {
            Schema::create('surat_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('jenis', 20)->default('keluar');
                $table->string('nama');
                $table->string('perihal_template')->nullable();
                $table->text('isi_template');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        foreach (['surat_masuk', 'surat_keluar'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'verification_token')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('verification_token', 64)->nullable()->unique()->after('id');
                });
            }
        }

        if (Schema::hasTable('surat_keluar') && ! Schema::hasColumn('surat_keluar', 'isi_surat')) {
            Schema::table('surat_keluar', function (Blueprint $table) {
                $table->text('isi_surat')->nullable()->after('perihal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('surat_keluar') && Schema::hasColumn('surat_keluar', 'isi_surat')) {
            Schema::table('surat_keluar', fn (Blueprint $table) => $table->dropColumn('isi_surat'));
        }

        foreach (['surat_masuk', 'surat_keluar'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'verification_token')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropUnique(['verification_token']);
                    $table->dropColumn('verification_token');
                });
            }
        }

        Schema::dropIfExists('surat_templates');
        Schema::dropIfExists('letter_number_sequences');
    }
};
