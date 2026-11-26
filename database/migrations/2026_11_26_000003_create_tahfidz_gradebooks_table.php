<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku nilai tahfidz diikat ke HALAQAH, bukan rombel.
 *
 * Buku nilai mapel umum memakai satu baris `teacher_admin_books` per
 * (guru × mapel × rombel). Pola itu tidak berlaku untuk tahfidz: satu
 * halaqah boleh berisi santri dari beberapa rombel dalam satu tingkat, dan
 * satu rombel bisa terbagi ke beberapa halaqah. Jika buku tahfidz tetap
 * diikat ke rombel, guru halaqah kehilangan santri yang rombelnya tidak ia
 * ajar dan nilai dua halaqah tercampur dalam satu buku.
 *
 * Karena itu buku tahfidz punya tabel sendiri, dengan kunci halaqah +
 * mapel + tahun ajaran + semester. Nilai per santri dipetakan balik ke
 * rombel masing-masing saat masuk rapor.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tahfidz_gradebooks')) {
            return;
        }

        Schema::create('tahfidz_gradebooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tahfidz_group_id');
            $table->uuid('subject_id');
            $table->uuid('academic_year_id');
            $table->uuid('school_id');
            $table->enum('semester', ['ganjil', 'genap']);

            // Bobot NR Final 4 komponen. Default mengikuti format tahfidz:
            // ziyadah paling besar, rabt & STS menengah, SAS penutup.
            $table->decimal('weight_ziyadah', 4, 1)->default(40.0);
            $table->decimal('weight_rabt', 4, 1)->default(20.0);
            $table->decimal('weight_sts', 4, 1)->default(20.0);
            $table->decimal('weight_sas', 4, 1)->default(20.0);

            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            $table->unique(
                ['tahfidz_group_id', 'subject_id', 'academic_year_id', 'semester'],
                'unique_tahfidz_gradebook_per_group'
            );

            $table->index(['school_id', 'academic_year_id', 'semester'], 'idx_tahfidz_gradebook_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_gradebooks');
    }
};
