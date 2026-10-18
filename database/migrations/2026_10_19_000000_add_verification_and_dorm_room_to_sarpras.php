<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (! Schema::hasColumn('assets', 'verification_status')) {
                    $table->string('verification_status', 20)->default('verified')->after('status');
                }
                if (! Schema::hasColumn('assets', 'verified_by')) {
                    $table->foreignUuid('verified_by')->nullable()->after('verification_status')
                        ->constrained('users', 'id')->nullOnDelete();
                }
                if (! Schema::hasColumn('assets', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('verified_by');
                }
                if (! Schema::hasColumn('assets', 'verification_note')) {
                    $table->text('verification_note')->nullable()->after('verified_at');
                }
            });
        }

        if (Schema::hasTable('asset_rooms') && ! Schema::hasColumn('asset_rooms', 'dormitory_room_id')) {
            Schema::table('asset_rooms', function (Blueprint $table) {
                $table->foreignUuid('dormitory_room_id')->nullable()->after('study_group_id')
                    ->constrained('dormitory_rooms')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('asset_rooms') && Schema::hasColumn('asset_rooms', 'dormitory_room_id')) {
            Schema::table('asset_rooms', fn (Blueprint $table) => $table->dropConstrainedForeignId('dormitory_room_id'));
        }

        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (Schema::hasColumn('assets', 'verified_by')) $table->dropConstrainedForeignId('verified_by');
                foreach (['verification_status', 'verified_at', 'verification_note'] as $col) {
                    if (Schema::hasColumn('assets', $col)) $table->dropColumn($col);
                }
            });
        }
    }
};
