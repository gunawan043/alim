<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_damage_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignUuid('reported_by')->constrained('users')->onDelete('cascade');
            $table->string('report_number')->unique();
            $table->enum('damage_level', ['ringan', 'sedang', 'berat'])->default('ringan');
            $table->text('description');
            $table->text('reporter_notes')->nullable();
            $table->json('photos')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'scheduled', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->onDelete('set null');
            $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_damage_reports');
    }
};
