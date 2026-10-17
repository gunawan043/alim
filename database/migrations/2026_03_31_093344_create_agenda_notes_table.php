<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agenda_id')->unique()
                ->comment('1:1 dengan agendas — satu agenda satu notulensi');
            $table->longText('content')->nullable()
                ->comment('Isi notulensi lengkap');
            $table->text('key_decisions')->nullable()
                ->comment('Poin-poin keputusan utama dari rapat/kegiatan');
            $table->text('follow_up_actions')->nullable()
                ->comment('Tindak lanjut yang disepakati beserta penanggung jawab');
            $table->date('next_meeting_date')->nullable();
            $table->string('attachment_path', 255)->nullable()
                ->comment('File notulensi resmi dalam format PDF/DOCX');
            $table->uuid('written_by')
                ->comment('Notulis yang menulis catatan ini');
            $table->uuid('approved_by')->nullable()
                ->comment('Pimpinan yang menyetujui notulensi');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('agenda_id')
                ->references('id')->on('agendas')->cascadeOnDelete();
            $table->foreign('written_by')
                ->references('id')->on('users');
            $table->foreign('approved_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_notes');
    }
};
