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
            DROP PROCEDURE IF EXISTS get_gtk_report;
            
            CREATE PROCEDURE get_gtk_report(
                IN p_school_id CHAR(36),
                IN p_academic_year_id CHAR(36)
            )
            BEGIN
                -- Structural positions
                SELECT 
                    u.id AS user_id,
                    u.name AS teacher_name,
                    u.nuptk,
                    sp.name AS position_name,
                    "Struktural" AS assignment_type,
                    sa.start_date,
                    sa.status
                FROM structural_assignments sa
                JOIN users u ON sa.user_id = u.id
                JOIN structural_positions sp ON sa.position_id = sp.id
                WHERE sa.school_id = p_school_id
                AND sa.academic_year_id = p_academic_year_id
                AND sa.status = "active"
                
                UNION ALL
                
                -- Homeroom teachers
                SELECT 
                    u.id,
                    u.name,
                    u.nuptk,
                    CONCAT("Wali Kelas ", sg.name) AS position_name,
                    "Wali Kelas" AS assignment_type,
                    ha.start_date,
                    ha.status
                FROM homeroom_assignments ha
                JOIN users u ON ha.teacher_id = u.id
                JOIN study_groups sg ON ha.study_group_id = sg.id
                WHERE ha.school_id = p_school_id
                AND ha.academic_year_id = p_academic_year_id
                AND ha.status = "active"
                
                UNION ALL
                
                -- Coordinators
                SELECT 
                    u.id,
                    u.name,
                    u.nuptk,
                    CASE 
                        WHEN ca.coordinator_type = "tingkat" THEN CONCAT("Koordinator ", gl.name)
                        WHEN ca.coordinator_type = "bidang" THEN CONCAT("Koordinator Bidang ", ca.field_name)
                        WHEN ca.coordinator_type = "mapel" THEN CONCAT("Koordinator ", s.name)
                        ELSE CONCAT("Koordinator ", ca.coordinator_type)
                    END AS position_name,
                    "Koordinator" AS assignment_type,
                    ca.start_date,
                    ca.status
                FROM coordinator_assignments ca
                JOIN users u ON ca.coordinator_id = u.id
                LEFT JOIN grade_levels gl ON ca.grade_level_id = gl.id
                LEFT JOIN subjects s ON ca.subject_id = s.id
                WHERE ca.school_id = p_school_id
                AND ca.academic_year_id = p_academic_year_id
                AND ca.status = "active"
                
                ORDER BY assignment_type, position_name;
            END
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS get_gtk_report');
    }
};
