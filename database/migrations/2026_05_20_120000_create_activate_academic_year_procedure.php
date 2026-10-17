<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('
            DROP PROCEDURE IF EXISTS activate_academic_year;

            CREATE PROCEDURE activate_academic_year(
                IN p_academic_year_id CHAR(36)
            )
            BEGIN
                DECLARE v_school_id CHAR(36);
                DECLARE v_old_academic_year_id CHAR(36);

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    SELECT "Error: Activate academic year failed" AS message;
                END;

                START TRANSACTION;

                -- Get school_id from academic year
                SELECT school_id INTO v_school_id
                FROM academic_years
                WHERE id = p_academic_year_id;

                -- Get old active academic year
                SELECT id INTO v_old_academic_year_id
                FROM academic_years
                WHERE school_id = v_school_id
                AND is_active = true
                AND id != p_academic_year_id
                LIMIT 1;

                -- Deactivate old academic year
                IF v_old_academic_year_id IS NOT NULL THEN
                    UPDATE academic_years
                    SET is_active = false, updated_at = NOW()
                    WHERE id = v_old_academic_year_id;

                    -- Update status all assignments to inactive
                    UPDATE structural_assignments
                    SET status = "ended", updated_at = NOW()
                    WHERE academic_year_id = v_old_academic_year_id;

                    UPDATE teaching_assignments
                    SET status = "inactive", updated_at = NOW()
                    WHERE academic_year_id = v_old_academic_year_id;

                    UPDATE homeroom_assignments
                    SET status = "ended", updated_at = NOW()
                    WHERE academic_year_id = v_old_academic_year_id;

                    UPDATE coordinator_assignments
                    SET status = "ended", updated_at = NOW()
                    WHERE academic_year_id = v_old_academic_year_id;
                END IF;

                -- Activate new academic year
                UPDATE academic_years
                SET is_active = true, updated_at = NOW()
                WHERE id = p_academic_year_id;

                COMMIT;

                SELECT "Academic year activated successfully" AS message;
            END
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS activate_academic_year');
    }
};
