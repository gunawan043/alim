<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_supervisors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->comment('Musyrif / musyrifah');
            $table->uuid('room_id');
            $table->uuid('dormitory_id');
            $table->uuid('academic_year_id');
            $table->uuid('decree_id')->nullable()->comment('SK penugasan wali kamar');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'ended'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('decree_id')->references('id')->on('institution_decrees')->nullOnDelete();
 
            // Satu kamar hanya boleh punya satu wali aktif per tahun ajaran
            $table->unique(
                ['room_id', 'academic_year_id', 'status'],
                'unique_active_supervisor_per_room'
            );
            $table->index(['user_id', 'academic_year_id']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('room_supervisors');
    }
};
