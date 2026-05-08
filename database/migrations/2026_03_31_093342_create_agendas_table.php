<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->uuid('agenda_category_id')->nullable();
 
            // --- SCOPE PEMILIK ---
            // Tepat satu dari empat kolom ini yang aktif, sisanya NULL,
            // ditentukan oleh kolom `scope`.
            $table->enum('scope', ['ponpes', 'sekolah', 'unit_kerja', 'individu'])
                  ->comment('Konteks pemilik agenda');
            $table->uuid('work_unit_id')->nullable()
                  ->comment('Diisi jika scope = ponpes atau unit_kerja');
            $table->uuid('school_id')->nullable()
                  ->comment('Diisi jika scope = sekolah');
            $table->uuid('owner_id')
                  ->comment('Pembuat / penanggung jawab agenda');
 
            // --- WAKTU ---
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->tinyInteger('is_all_day')->default(0)
                  ->comment('1 = kegiatan seharian, abaikan jam');
            $table->string('timezone', 50)->default('Asia/Jakarta');
 
            // --- LOKASI ---
            $table->string('location_name', 191)->nullable();
            $table->text('location_address')->nullable();
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->string('location_url', 255)->nullable()
                  ->comment('Link Maps, Zoom, Meet, dll');
 
            // --- PENGULANGAN (iCal RFC 5545) ---
            $table->tinyInteger('is_recurring')->default(0);
            $table->string('recurrence_rule', 255)->nullable()
                  ->comment('Format RRULE iCal, misal: FREQ=WEEKLY;BYDAY=MO,WE');
            $table->date('recurrence_end_date')->nullable();
            $table->uuid('parent_agenda_id')->nullable()
                  ->comment('Induk agenda jika ini adalah instance pengulangan');
 
            // --- VISIBILITAS & STATUS ---
            $table->enum('visibility', ['publik', 'internal', 'privat'])
                  ->default('internal')
                  ->comment('publik=wali santri bisa lihat, internal=GTK saja, privat=undangan saja');
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])
                  ->default('draft');
            $table->tinyInteger('is_mandatory')->default(0)
                  ->comment('1 = wajib dihadiri oleh peserta yang diundang');
 
            // --- LAMPIRAN & METADATA ---
            $table->string('attachment_path', 255)->nullable();
            $table->string('color', 20)->nullable()
                  ->comment('Override warna kategori untuk agenda ini');
            $table->string('tags', 255)->nullable()
                  ->comment('Tag comma-separated untuk filter & pencarian');
            $table->uuid('academic_year_id')->nullable();
 
            // --- PEMBATALAN ---
            $table->text('cancelled_reason')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
 
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();
 
            // --- FOREIGN KEYS ---
            $table->foreign('agenda_category_id')
                  ->references('id')->on('agenda_categories')->nullOnDelete();
            $table->foreign('work_unit_id')
                  ->references('id')->on('work_units')->nullOnDelete();
            $table->foreign('school_id')
                  ->references('id')->on('schools')->nullOnDelete();
            $table->foreign('owner_id')
                  ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_agenda_id')
                  ->references('id')->on('agendas')->nullOnDelete();
            $table->foreign('academic_year_id')
                  ->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('cancelled_by')
                  ->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')
                  ->references('id')->on('users');
 
            // --- INDEXES ---
            $table->index(['scope', 'work_unit_id', 'start_datetime']);
            $table->index(['scope', 'school_id', 'start_datetime']);
            $table->index(['owner_id', 'start_datetime']);
            $table->index(['status', 'visibility', 'start_datetime']);
            $table->index(['start_datetime', 'end_datetime']);
            $table->index(['parent_agenda_id']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
