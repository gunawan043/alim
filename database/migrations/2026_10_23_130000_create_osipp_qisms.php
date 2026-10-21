<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osipp_qisms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('osipp_periods')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('leader_member_id')->nullable()->constrained('osipp_members')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['period_id', 'name'], 'osipp_qism_period_name');
            $table->unique(['period_id', 'code'], 'osipp_qism_period_code');
            $table->index(['period_id', 'is_active'], 'osipp_qism_period_active');
        });

        Schema::table('osipp_members', function (Blueprint $table) {
            $table->foreignUuid('qism_id')->nullable()->after('member_role')
                ->constrained('osipp_qisms')->nullOnDelete();
            $table->index(['period_id', 'qism_id'], 'osipp_member_period_qism');
        });

        // Data lama memakai kategori "pengurus"; struktur baru membedakan
        // pengurus inti dari anggota/ketua qism.
        DB::table('osipp_members')
            ->where('member_role', 'pengurus')
            ->update(['member_role' => 'pengurus_inti']);
    }

    public function down(): void
    {
        Schema::table('osipp_members', function (Blueprint $table) {
            $table->dropForeign(['qism_id']);
            $table->dropIndex('osipp_member_period_qism');
            $table->dropColumn('qism_id');
        });

        DB::table('osipp_members')
            ->where('member_role', 'pengurus_inti')
            ->update(['member_role' => 'pengurus']);

        Schema::dropIfExists('osipp_qisms');
    }
};
