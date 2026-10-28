<?php

use App\Services\Academic\StudyGroupRoomService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill data lama: rombel yang sudah ada tetapi belum memiliki
     * ruang kelas di Sarpras. Aman dijalankan berulang karena memakai
     * updateOrCreate berdasarkan study_group_id.
     */
    public function up(): void
    {
        if (! Schema::hasTable('study_groups') || ! Schema::hasTable('asset_rooms')) {
            return;
        }

        app(StudyGroupRoomService::class)->syncAll();
    }

    public function down(): void
    {
        // Sengaja tidak menghapus ruang/aset yang sudah terhubung.
    }
};
