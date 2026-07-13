<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_list_id')->nullable()
                  ->comment('NULL = masuk ke daftar default owner');
 
            // --- PEMILIK & DELEGASI ---
            $table->uuid('owner_id')
                  ->comment('GTK yang bertanggung jawab menyelesaikan tugas');
            $table->uuid('created_by')
                  ->comment('Pembuat tugas. Bisa sama dengan owner (mandiri) atau berbeda (delegasi)');
            $table->uuid('delegated_by')->nullable()
                  ->comment('Atasan yang mendelegasikan. NULL jika tugas mandiri');
            $table->timestamp('delegated_at')->nullable();
 
            // --- KONTEN ---
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('priority', ['rendah', 'sedang', 'tinggi', 'mendesak'])
                  ->default('sedang');
            $table->string('tags', 255)->nullable();
 
            // --- WAKTU ---
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->dateTime('reminder_at')->nullable()
                  ->comment('Waktu pengingat, trigger notifikasi otomatis');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
 
            // --- STATUS & PROGRESS ---
            $table->enum('status', [
                'belum_mulai',
                'sedang_berjalan',
                'selesai',
                'dibatalkan',
                'ditunda',
            ])->default('belum_mulai');
            $table->tinyInteger('progress_percent')->default(0)
                  ->comment('Persentase penyelesaian 0-100, dihitung otomatis dari subtask');
            $table->tinyInteger('is_pinned')->default(0)
                  ->comment('1 = disematkan di bagian atas daftar');
            $table->tinyInteger('is_private')->default(0)
                  ->comment('1 = hanya bisa dilihat owner, tidak terlihat pengamat/watcher');
 
            // --- RELASI KE MODUL LAIN (polymorphic) ---
            $table->uuid('related_agenda_id')->nullable()
                  ->comment('Jika tugas ini terkait agenda tertentu');
            $table->string('related_type', 100)->nullable()
                  ->comment('Nama model terkait: agenda, surat_keluar, dll');
            $table->char('related_id', 36)->nullable()
                  ->comment('ID record terkait');
 
            // --- KONTEKS INSTITUSIONAL ---
            $table->uuid('work_unit_id')->nullable()
                  ->comment('NULL = tugas murni pribadi');
            $table->uuid('school_id')->nullable();
            $table->uuid('academic_year_id')->nullable();
 
            $table->integer('sort_order')->default(0);
            $table->text('cancelled_reason')->nullable();
            $table->uuid('created_at_timezone')->nullable()
                  ->comment('Timezone saat dibuat, untuk tampilan yang benar');
 
            $table->timestamps();
            $table->softDeletes();
 
            // --- FOREIGN KEYS ---
            $table->foreign('todo_list_id')
                  ->references('id')->on('todo_lists')->nullOnDelete();
            $table->foreign('owner_id')
                  ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by')
                  ->references('id')->on('users');
            $table->foreign('delegated_by')
                  ->references('id')->on('users')->nullOnDelete();
            $table->foreign('related_agenda_id')
                  ->references('id')->on('agendas')->nullOnDelete();
            $table->foreign('work_unit_id')
                  ->references('id')->on('work_units')->nullOnDelete();
            $table->foreign('school_id')
                  ->references('id')->on('schools')->nullOnDelete();
            $table->foreign('academic_year_id')
                  ->references('id')->on('academic_years')->nullOnDelete();
 
            // --- INDEXES ---
            $table->index(['owner_id', 'status', 'due_date']);
            $table->index(['owner_id', 'is_pinned', 'sort_order']);
            $table->index(['delegated_by', 'status']);
            $table->index(['reminder_at', 'status']);
            $table->index(['related_type', 'related_id']);
            $table->index(['work_unit_id', 'status']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
