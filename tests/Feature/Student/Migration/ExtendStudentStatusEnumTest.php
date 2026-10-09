<?php

namespace Tests\Feature\Student\Migration;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExtendStudentStatusEnumTest extends TestCase
{
    /** @test */
    public function students_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('students'));
    }

    /** @test */
    public function students_status_column_exists(): void
    {
        $this->assertTrue(Schema::hasColumn('students', 'status'));
    }

    /** @test */
    public function students_audit_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('student_lifecycle_audits'));
    }

    /** @test */
    public function students_audit_table_has_required_columns(): void
    {
        // Kolom riil mengikuti migrasi 2026_06_18_020100 (event/actor_id/payload/occurred_at).
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'id'));
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'event'));
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'student_id'));
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'school_id'));
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'actor_id'));
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'payload'));
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'occurred_at'));
        $this->assertTrue(Schema::hasColumn('student_lifecycle_audits', 'created_at'));
    }
}
