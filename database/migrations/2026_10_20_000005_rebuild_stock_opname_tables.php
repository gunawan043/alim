<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasData = false;
        foreach (['stock_opname_sessions', 'stock_opname_items', 'stock_opname_officers'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                $hasData = true;
            }
        }

        // Tabel lama memakai BIGINT untuk school_id/created_by padahal aplikasi memakai UUID.
        // Aman dibangun ulang hanya bila belum ada data; bila ada data, jangan dihapus.
        if ($hasData) {
            $this->addMissingColumns();

            return;
        }

        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opname_officers');
        Schema::dropIfExists('stock_opname_sessions');

        Schema::create('stock_opname_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('session_code')->unique();
            $table->uuid('work_unit_id')->nullable();
            $table->uuid('school_id')->nullable();
            $table->uuid('room_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->date('started_date')->nullable();
            $table->date('closed_date')->nullable();
            $table->string('status', 30)->default('planned');
            $table->uuid('created_by');
            $table->uuid('closed_by')->nullable();
            $table->text('summary_notes')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->nullOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('asset_categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['work_unit_id', 'status']);
            $table->index('scheduled_date');
        });

        Schema::create('stock_opname_officers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('session_id');
            $table->uuid('user_id');
            $table->string('role', 30)->default('officer');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('stock_opname_sessions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['session_id', 'user_id']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('session_id');
            $table->uuid('asset_id');
            $table->string('expected_status', 30)->nullable();
            $table->string('expected_condition', 30)->nullable();
            $table->uuid('expected_room_id')->nullable();
            $table->string('observed_status', 30)->default('pending');
            $table->string('observed_condition', 30)->nullable();
            $table->uuid('observed_room_id')->nullable();
            $table->string('actual_location')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('stock_opname_sessions')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('expected_room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('observed_room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['session_id', 'asset_id']);
            $table->index('observed_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opname_officers');
        Schema::dropIfExists('stock_opname_sessions');
    }

    private function addMissingColumns(): void
    {
        Schema::table('stock_opname_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_opname_sessions', 'room_id')) {
                $table->uuid('room_id')->nullable();
            }
            if (! Schema::hasColumn('stock_opname_sessions', 'category_id')) {
                $table->uuid('category_id')->nullable();
            }
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_opname_items', 'expected_condition')) {
                $table->string('expected_condition', 30)->nullable();
            }
            if (! Schema::hasColumn('stock_opname_items', 'observed_condition')) {
                $table->string('observed_condition', 30)->nullable();
            }
            if (! Schema::hasColumn('stock_opname_items', 'actual_location')) {
                $table->string('actual_location')->nullable();
            }
            if (! Schema::hasColumn('stock_opname_items', 'photo_path')) {
                $table->string('photo_path')->nullable();
            }
        });
    }
};
