<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_emergency_broadcasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id');
            $table->string('title');
            $table->longText('content');
            $table->enum('severity', ['info', 'warning', 'urgent', 'emergency'])->default('info');
            $table->enum('broadcast_via', ['whatsapp', 'inapp', 'all'])->default('all');
            $table->boolean('ack_required')->default(false)->comment('Wali wajib ACK sebelum dianggap baca');
            $table->dateTime('expires_at')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dormitory_id')->references('id')->on('dormitories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['dormitory_id', 'severity']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_emergency_broadcasts');
    }
};