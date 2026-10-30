<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('humas_announcements', function (Blueprint $table) {
            $table->string('target_type', 20)->default('global')->after('school_id');
            $table->uuid('dormitory_id')->nullable()->after('target_type');
            $table->uuid('study_group_id')->nullable()->after('dormitory_id');
            $table->string('severity', 20)->default('info')->after('category');
            $table->json('channels')->nullable()->after('severity');
            $table->boolean('ack_required')->default(false)->after('channels');
            $table->string('recipient_group', 20)->default('wali')->after('ack_required');

            $table->index(['target_type', 'school_id']);
            $table->index('dormitory_id');
            $table->index('study_group_id');
        });

        DB::table('humas_announcements')->update([
            'target_type' => DB::raw("CASE WHEN school_id IS NULL THEN 'global' ELSE 'school' END"),
        ]);

        Schema::create('announcement_acknowledgements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_type', 191);
            $table->uuid('reference_id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('student_id')->nullable();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(
                ['reference_type', 'reference_id', 'user_id', 'student_id'],
                'announcement_ack_unique'
            );
            $table->index(['user_id', 'reference_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_acknowledgements');

        Schema::table('humas_announcements', function (Blueprint $table) {
            $table->dropIndex(['target_type', 'school_id']);
            $table->dropIndex(['dormitory_id']);
            $table->dropIndex(['study_group_id']);
            $table->dropColumn([
                'target_type', 'dormitory_id', 'study_group_id',
                'severity', 'channels', 'ack_required', 'recipient_group',
            ]);
        });
    }
};
