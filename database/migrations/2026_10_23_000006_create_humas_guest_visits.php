<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('humas_guest_visits')) {
            Schema::create('humas_guest_visits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('visitor_name');
                $table->string('institution')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('person_to_meet')->nullable();
                $table->string('purpose', 200);
                $table->string('status', 20)->default('checked_in');
                $table->timestamp('check_in_at');
                $table->timestamp('check_out_at')->nullable();
                $table->foreignUuid('officer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['school_id', 'status', 'check_in_at'], 'humas_guest_visit_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('humas_guest_visits');
    }
};
