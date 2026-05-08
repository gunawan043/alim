<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_presensi_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('presensi_mapel_id')->constrained('admin_presensi_mapel')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['presensi_mapel_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_presensi_siswa');
    }
};