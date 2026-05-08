<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agenda_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agenda_id');
            $table->uuid('user_id')->nullable()
                  ->comment('NULL = reminder untuk semua peserta agenda ini');
            $table->integer('remind_before_minutes')
                  ->comment('Menit sebelum agenda dimulai. Misal: 1440=1 hari, 60=1 jam, 15=15 menit');
            $table->dateTime('remind_at')
                  ->comment('Waktu pengiriman, dihitung dari start_datetime - remind_before_minutes');
            $table->enum('channel', ['in_app', 'email', 'whatsapp', 'push'])
                  ->default('in_app');
            $table->tinyInteger('is_sent')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
 
            $table->foreign('agenda_id')
                  ->references('id')->on('agendas')->cascadeOnDelete();
            $table->foreign('user_id')
                  ->references('id')->on('users')->nullOnDelete();
 
            $table->index(['is_sent', 'remind_at']);
            $table->index(['agenda_id', 'channel']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('agenda_reminders');
    }
};
