<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extends students.status enum to include:
 *  - transfer_in  (pindahan masuk)
 *  - transfer_out (pindahan keluar)
 *
 * Defensively remaps any legacy 'transfer' rows to 'transfer_out' since
 * StudentMutationOutController::approve() historically attempted to write
 * the 'transfer' value on promotion mutate_out, and the new lifecycle
 * event bus needs the in/out distinction to be observable.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE students SET status = 'transfer_out' WHERE status = 'transfer'");

        DB::statement("
            ALTER TABLE students
            MODIFY COLUMN status
            ENUM('active','inactive','graduate','dropped','transfer_in','transfer_out')
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        DB::statement("UPDATE students SET status = 'transfer' WHERE status IN ('transfer_in','transfer_out')");

        DB::statement("
            ALTER TABLE students
            MODIFY COLUMN status
            ENUM('active','inactive','graduate','dropped','transfer')
            NOT NULL DEFAULT 'active'
        ");
    }
};
