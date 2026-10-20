<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\LibraryGenre;
use App\Models\LibraryItem;
use App\Models\LibraryMember;
use App\Models\LibraryMemberCard;
use App\Models\LibraryTitle;
use App\Models\LibraryUnit;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_mvp_perpustakaan_katalog_anggota_kartu_dan_peminjaman(): void
    {
        $unit = WorkUnit::create(['name' => 'Perpustakaan', 'code' => 'PERPUS-TEST', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '99999999', 'name' => 'Sekolah Uji']);
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $admin = User::create(['name' => 'Admin Perpus', 'email' => 'admin.perpus@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $library = LibraryUnit::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id,
            'code' => 'PERPUS-01', 'name' => 'Perpustakaan Putra', 'gender_scope' => 'L', 'level_scope' => 'sd_smp', 'is_active' => true,
        ]);
        $genre = LibraryGenre::create(['school_id' => $school->id, 'name' => 'Sejarah', 'is_active' => true]);
        $title = LibraryTitle::create(['school_id' => $school->id, 'genre_id' => $genre->id, 'title' => 'Buku Uji', 'author' => 'Penulis', 'is_active' => true]);
        $item = LibraryItem::create(['library_title_id' => $title->id, 'library_unit_id' => $library->id, 'inventory_code' => 'INV-001', 'status' => LibraryItem::STATUS_AVAILABLE, 'condition' => 'baik']);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Uji', 'nis' => 'S-001', 'nisn' => 'S-001', 'gender' => 'L', 'status' => 'active']);

        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan")->assertOk()->assertSee('Dashboard');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/catalog?unit_id={$library->id}")->assertOk()->assertSee('Buku Uji');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/catalog/labels?unit_id={$library->id}")->assertOk();

        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/members", [
            'library_unit_id' => $library->id,
            'member_type' => 'student',
            'student_id' => $student->id,
            'joined_at' => now()->toDateString(),
        ])->assertRedirect();

        $member = LibraryMember::firstOrFail();
        $card = LibraryMemberCard::where('library_member_id', $member->id)->firstOrFail();
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/members?unit_id={$library->id}")->assertOk()->assertSee('Santri Uji');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/members/{$member->id}/card")->assertOk();

        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/loans", [
            'library_unit_id' => $library->id,
            'library_member_id' => $member->id,
            'card_number' => $card->card_number,
            'physical_card_verified' => 1,
            'loan_date' => now()->toDateString(),
            'item_ids' => [$item->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('library_loans', ['library_member_id' => $member->id, 'status' => 'active']);
        $this->assertDatabaseHas('library_items', ['id' => $item->id, 'status' => 'on_loan']);
    }
}
