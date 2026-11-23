<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak header kontrak yang dikirim ALIM Connect.
     *
     * `Idempotency-Key` dikunci sama dengan `command_id` oleh middleware,
     * tetapi tetap disimpan agar audit dapat membuktikan header mana yang
     * diterima. Nullable: klien lama yang belum mengirim header tetap sah.
     */
    public function up(): void
    {
        Schema::table('approval_commands', function (Blueprint $table) {
            $table->string('idempotency_key', 100)
                ->nullable()
                ->after('command_id')
                ->comment('Header Idempotency-Key; harus sama dengan command_id');

            $table->string('api_version', 16)
                ->nullable()
                ->after('idempotency_key')
                ->comment('Header X-ALIM-API-Version yang diterima');
        });
    }

    public function down(): void
    {
        Schema::table('approval_commands', function (Blueprint $table) {
            $table->dropColumn(['idempotency_key', 'api_version']);
        });
    }
};
