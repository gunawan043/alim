<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip on SQLite: anonymous char(36) columns. Not needed for academic tests.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Pivot untuk BankSoal <-> TujuanPembelajaran
        Schema::create('bank_soal_tp', function (Blueprint $table) {
            $table->uuid('bank_soal_id');
            $table->uuid('tp_id');
            $table->timestamps();

            $table->primary(['bank_soal_id', 'tp_id']);
            $table->foreign('bank_soal_id')->references('id')->on('bank_soal')->onDelete('cascade');
            $table->foreign('tp_id')->references('id')->on('tujuan_pembelajaran')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soal_tp');
    }
};
