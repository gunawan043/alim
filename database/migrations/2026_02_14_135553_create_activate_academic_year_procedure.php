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
            DROP PROCEDURE IF EXISTS generate_teaching_decree;
            
            CREATE PROCEDURE generate_teaching_decree(
                IN p_school_id CHAR(36),
                IN p_academic_year_id CHAR(36),
                IN p_decree_number VARCHAR(100),
                IN p_signed_by CHAR(36),
                IN p_issued_date DATE
            )
            BEGIN
                DECLARE v_decree_id CHAR(36);
                DECLARE v_school_name VARCHAR(255);
                DECLARE v_academic_year VARCHAR(50);
                DECLARE v_counter INT DEFAULT 0;
                
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    SELECT "Error: Generate teaching decree failed" AS message;
                END;

                START TRANSACTION;

                -- Ambil informasi sekolah dan tahun ajaran
                SELECT name INTO v_school_name FROM schools WHERE id = p_school_id;
                SELECT name INTO v_academic_year FROM academic_years WHERE id = p_academic_year_id;

                -- Buat SK baru
                SET v_decree_id = UUID();
                INSERT INTO institution_decrees (
                    id, decree_number, decree_type, title, description,
                    academic_year_id, issued_date, effective_date, 
                    signed_by, signed_position, status, created_at, updated_at
                ) VALUES (
                    v_decree_id,
                    p_decree_number,
                    "SK_PEMBAGIAN_TUGAS",
                    CONCAT("SK Pembagian Tugas Mengajar ", v_academic_year, " - ", v_school_name),
                    "Surat Keputusan tentang Pembagian Tugas Mengajar dan Tugas Tambahan",
                    p_academic_year_id,
                    p_issued_date,
                    p_issued_date,
                    p_signed_by,
                    (SELECT signed_position FROM institution_decrees WHERE signed_by = p_signed_by LIMIT 1),
                    "active",
                    NOW(),
                    NOW()
                );

                -- Copy data dari teaching_assignments sebelumnya (jika ada)
                INSERT INTO teaching_assignments (
                    id, decree_id, teacher_id, school_id, academic_year_id,
                    study_group_id, subject_id, role, is_coordinator, weekly_hours,
                    status, created_at, updated_at
                )
                SELECT 
                    UUID(), v_decree_id, ta.teacher_id, ta.school_id, p_academic_year_id,
                    ta.study_group_id, ta.subject_id, ta.role, ta.is_coordinator, ta.weekly_hours,
                    "active", NOW(), NOW()
                FROM teaching_assignments ta
                WHERE ta.academic_year_id = (
                    SELECT id FROM academic_years 
                    WHERE school_id = p_school_id 
                    AND name < (SELECT name FROM academic_years WHERE id = p_academic_year_id)
                    ORDER BY name DESC LIMIT 1
                )
                AND ta.status = "active";

                -- Copy data homeroom assignments
                INSERT INTO homeroom_assignments (
                    id, decree_id, teacher_id, study_group_id, school_id,
                    academic_year_id, start_date, status, created_at, updated_at
                )
                SELECT 
                    UUID(), v_decree_id, ha.teacher_id, ha.study_group_id, ha.school_id,
                    p_academic_year_id, p_issued_date, "active", NOW(), NOW()
                FROM homeroom_assignments ha
                WHERE ha.academic_year_id = (
                    SELECT id FROM academic_years 
                    WHERE school_id = p_school_id 
                    AND name < (SELECT name FROM academic_years WHERE id = p_academic_year_id)
                    ORDER BY name DESC LIMIT 1
                )
                AND ha.status = "active";

                -- Copy data coordinator assignments
                INSERT INTO coordinator_assignments (
                    id, decree_id, coordinator_id, school_id, academic_year_id,
                    coordinator_type, grade_level_id, subject_id, field_name,
                    start_date, status, created_at, updated_at
                )
                SELECT 
                    UUID(), v_decree_id, ca.coordinator_id, ca.school_id, p_academic_year_id,
                    ca.coordinator_type, ca.grade_level_id, ca.subject_id, ca.field_name,
                    p_issued_date, "active", NOW(), NOW()
                FROM coordinator_assignments ca
                WHERE ca.academic_year_id = (
                    SELECT id FROM academic_years 
                    WHERE school_id = p_school_id 
                    AND name < (SELECT name FROM academic_years WHERE id = p_academic_year_id)
                    ORDER BY name DESC LIMIT 1
                )
                AND ca.status = "active";

                -- Copy data structural assignments
                INSERT INTO structural_assignments (
                    id, user_id, position_id, school_id, academic_year_id,
                    decree_id, additional_info, start_date, status, created_at, updated_at
                )
                SELECT 
                    UUID(), sa.user_id, sa.position_id, sa.school_id, p_academic_year_id,
                    v_decree_id, sa.additional_info, p_issued_date, "active", NOW(), NOW()
                FROM structural_assignments sa
                WHERE sa.academic_year_id = (
                    SELECT id FROM academic_years 
                    WHERE school_id = p_school_id 
                    AND name < (SELECT name FROM academic_years WHERE id = p_academic_year_id)
                    ORDER BY name DESC LIMIT 1
                )
                AND sa.status = "active";

                COMMIT;
                
                SELECT v_decree_id AS decree_id, "Teaching decree generated successfully" AS message;
            END
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS generate_teaching_decree');
    }
};
