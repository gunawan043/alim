<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_employments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status_kepegawaian', ['PTT','PTY','Percobaan','Magang','GTT','GTY','KONTRAK']);
            $table->string('nupy', 50)->unique()->nullable();
            $table->string('satuan_kerja', 100)->nullable();
            $table->string('jenis_gtk', 100)->nullable();
            $table->string('jabatan', 100)->nullable();

            $table->date('tmt')->nullable();
            $table->string('nomor_sk', 100)->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->string('pangkat_golongan', 50)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_employments');
    }
};