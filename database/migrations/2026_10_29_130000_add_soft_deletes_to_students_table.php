<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 — Data santri tidak boleh hilang permanen.
 *
 * Menambahkan kolom deleted_at pada tabel students sehingga penghapusan data
 * santri menjadi "arsip" (soft delete). Seluruh riwayat yang bergantung pada
 * santri (rombel, mutasi, nilai, kehadiran, audit) tetap utuh dan data dapat
 * dipulihkan kembali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
