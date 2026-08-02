<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_attendees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agenda_id');

            // --- TIPE PESERTA (salah satu diisi) ---
            $table->enum('attendee_type', [
                'user',         // Individu GTK tertentu
                'role',         // Semua user dengan role tertentu
                'unit_kerja',   // Semua GTK di unit kerja tertentu
                'sekolah',      // Semua GTK di sekolah tertentu
                'study_group',  // Semua santri di rombel tertentu
                'external',     // Tamu dari luar ponpes
            ]);
            $table->uuid('user_id')->nullable();
            $table->uuid('role_id')->nullable();
            $table->uuid('work_unit_id')->nullable();
            $table->uuid('school_id')->nullable();
            $table->uuid('study_group_id')->nullable();
            $table->string('external_name', 191)->nullable();
            $table->string('external_email', 100)->nullable();
            $table->string('external_phone', 20)->nullable();

            // --- PERAN DALAM ACARA ---
            $table->enum('attendee_role', [
                'peserta',
                'pembicara',
                'panitia',
                'moderator',
                'notulen',
                'pimpinan',
            ])->default('peserta');

            // --- RSVP ---
            $table->enum('rsvp_status', ['pending', 'hadir', 'tidak_hadir', 'mungkin'])
                ->default('pending');
            $table->timestamp('rsvp_at')->nullable();
            $table->text('rsvp_note')->nullable();

            // --- KEHADIRAN AKTUAL ---
            $table->tinyInteger('actual_attended')->nullable()
                ->comment('NULL = belum dicatat | 1 = hadir | 0 = tidak hadir');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // --- FOREIGN KEYS ---
            $table->foreign('agenda_id')
                ->references('id')->on('agendas')->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('role_id')
                ->references('id')->on('roles')->nullOnDelete();
            $table->foreign('work_unit_id')
                ->references('id')->on('work_units')->nullOnDelete();
            $table->foreign('school_id')
                ->references('id')->on('schools')->nullOnDelete();
            $table->foreign('study_group_id')
                ->references('id')->on('study_groups')->nullOnDelete();

            // Satu user hanya bisa masuk satu kali sebagai peserta individu
            $table->unique(['agenda_id', 'user_id'], 'unique_agenda_user_attendee');
            $table->index(['agenda_id', 'attendee_type']);
            $table->index(['user_id', 'rsvp_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_attendees');
    }
};
