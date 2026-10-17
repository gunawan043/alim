<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_id');
            $table->unsignedBigInteger('maintenance_log_id')->nullable();
            $table->unsignedBigInteger('maintenance_schedule_id')->nullable();
            $table->string('maintenance_type'); // preventive, corrective, emergency
            $table->date('performed_date');
            $table->string('performed_by_name')->nullable();
            $table->unsignedBigInteger('performed_by_user_id')->nullable();
            $table->string('condition_before')->nullable();
            $table->string('condition_after')->nullable();
            $table->text('work_description')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->date('next_due_date')->nullable();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('maintenance_type');
            $table->index('performed_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_histories');
    }
};
