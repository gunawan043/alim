<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('must_change_password')->default(false)->after('is_active');
            });
        }

        if (! Schema::hasTable('student_operator_assignments')) {
            Schema::create('student_operator_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('operator_type', 20)->default('library');
                $table->foreignUuid('library_unit_id')->nullable()->constrained('library_units')->nullOnDelete();
                $table->foreignUuid('dormitory_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['student_id', 'operator_type', 'is_active'], 'student_operator_active_idx');
                $table->index(['library_unit_id', 'is_active'], 'student_operator_unit_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_operator_assignments');
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('must_change_password'));
        }
    }
};
