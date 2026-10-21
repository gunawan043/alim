<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kontrak_templates')) {
            Schema::create('kontrak_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('nama');
                $table->string('jenis', 50)->nullable();
                $table->longText('isi_template')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('kontrak_kerja') && ! Schema::hasColumn('kontrak_kerja', 'kontrak_template_id')) {
            Schema::table('kontrak_kerja', function (Blueprint $table) {
                $table->uuid('kontrak_template_id')->nullable()->after('jenis');
            });
        }

        if (Schema::hasTable('kinerja_penilaian')) {
            Schema::table('kinerja_penilaian', function (Blueprint $table) {
                if (! Schema::hasColumn('kinerja_penilaian', 'nilai_detail')) {
                    $table->json('nilai_detail')->nullable()->after('tanggal_penilaian');
                }
                if (! Schema::hasColumn('kinerja_penilaian', 'rekomendasi')) {
                    $table->text('rekomendasi')->nullable()->after('nilai_detail');
                }
                if (! Schema::hasColumn('kinerja_penilaian', 'status_rekomendasi')) {
                    $table->string('status_rekomendasi', 30)->nullable()->after('rekomendasi');
                }
            });
        }

        if (Schema::hasTable('gtk_requests')) {
            Schema::table('gtk_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('gtk_requests', 'approved_by')) {
                    $table->uuid('approved_by')->nullable()->after('status');
                }
                if (! Schema::hasColumn('gtk_requests', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
                if (! Schema::hasColumn('gtk_requests', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('approved_at');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak_templates');
        if (Schema::hasTable('kontrak_kerja') && Schema::hasColumn('kontrak_kerja', 'kontrak_template_id')) {
            Schema::table('kontrak_kerja', fn (Blueprint $table) => $table->dropColumn('kontrak_template_id'));
        }
        if (Schema::hasTable('kinerja_penilaian')) {
            foreach (['status_rekomendasi', 'rekomendasi', 'nilai_detail'] as $column) {
                if (Schema::hasColumn('kinerja_penilaian', $column)) {
                    Schema::table('kinerja_penilaian', fn (Blueprint $table) => $table->dropColumn($column));
                }
            }
        }
        if (Schema::hasTable('gtk_requests')) {
            foreach (['rejection_reason', 'approved_at', 'approved_by'] as $column) {
                if (Schema::hasColumn('gtk_requests', $column)) {
                    Schema::table('gtk_requests', fn (Blueprint $table) => $table->dropColumn($column));
                }
            }
        }
    }
};
