<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssetRoom;
use App\Models\LibraryGenre;
use App\Models\LibraryItem;
use App\Models\LibraryMember;
use App\Models\LibraryProgram;
use App\Models\LibraryMemberCard;
use App\Models\LibraryTitle;
use App\Models\LibraryUnit;
use App\Models\RoomBooking;
use App\Models\School;
use App\Models\Student;
use App\Models\LibraryStockOpname;
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

        $loan = \App\Models\LibraryLoan::firstOrFail();
        $oldDue = $loan->due_date->copy();
        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/loans/{$loan->id}/renew")->assertRedirect();
        $this->assertSame(1, $loan->fresh()->renewals_count);
        $this->assertTrue($loan->fresh()->due_date->greaterThan($oldDue));
    }

    public function test_ruang_dan_stock_opname_perpustakaan(): void
    {
        $workUnit = WorkUnit::create(['name' => 'Unit Perpus Ruang', 'code' => 'PERPUS-ROOM', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $workUnit->id, 'npsn' => '88888888', 'name' => 'Sekolah Ruang']);
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $admin = User::create(['name' => 'Admin Ruang', 'email' => 'admin.ruang@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $unit = LibraryUnit::create(['work_unit_id' => $workUnit->id, 'school_id' => $school->id, 'code' => 'PERPUS-R', 'name' => 'Perpustakaan Ruang', 'gender_scope' => 'mixed', 'level_scope' => 'sd_smp', 'is_active' => true]);
        $room = AssetRoom::create(['work_unit_id' => $workUnit->id, 'school_id' => $school->id, 'room_name' => 'Ruang Baca', 'room_type' => 'perpustakaan', 'is_bookable' => true, 'booking_requires_approval' => false, 'is_active' => true]);
        $genre = LibraryGenre::create(['school_id' => $school->id, 'name' => 'Umum', 'is_active' => true]);
        $title = LibraryTitle::create(['school_id' => $school->id, 'genre_id' => $genre->id, 'title' => 'Buku Stock', 'is_active' => true]);
        LibraryItem::create(['library_title_id' => $title->id, 'library_unit_id' => $unit->id, 'inventory_code' => 'STK-001', 'status' => 'available', 'condition' => 'baik']);

        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/rooms?unit_id={$unit->id}")->assertOk()->assertSee('Ruang Baca');
        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/rooms", [
            'library_unit_id' => $unit->id,
            'room_id' => $room->id,
            'event_name' => 'Bedah Buku',
            'purpose' => 'Kegiatan literasi',
            'booking_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'participants_count' => 20,
            'booking_source' => 'direct',
        ])->assertRedirect();
        $this->assertDatabaseHas('room_bookings', ['library_unit_id' => $unit->id, 'event_name' => 'Bedah Buku', 'status' => 'approved']);

        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/stock-opnames", [
            'library_unit_id' => $unit->id,
            'session_date' => now()->toDateString(),
        ])->assertRedirect();
        $session = LibraryStockOpname::firstOrFail();
        $row = $session->items()->firstOrFail();
        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/stock-opnames/{$session->id}/items/{$row->id}", [
            'finding' => 'found',
            'physical_location' => 'Rak A',
            'condition' => 'baik',
        ])->assertRedirect();
        $this->assertDatabaseHas('library_stock_opname_items', ['id' => $row->id, 'finding' => 'found']);
    }

    public function test_halaman_rak_laporan_denda_dan_program_perpustakaan(): void
    {
        $workUnit = WorkUnit::create(['name' => 'Unit Perpus Halaman', 'code' => 'PERPUS-PAGE', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $workUnit->id, 'npsn' => '77777777', 'name' => 'Sekolah Halaman']);
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $admin = User::create(['name' => 'Admin Halaman', 'email' => 'admin.halaman@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $unit = LibraryUnit::create(['work_unit_id' => $workUnit->id, 'school_id' => $school->id, 'code' => 'PERPUS-P', 'name' => 'Perpustakaan Halaman', 'gender_scope' => 'mixed', 'level_scope' => 'sd_smp', 'is_active' => true]);
        $genre = LibraryGenre::create(['school_id' => $school->id, 'name' => 'Referensi', 'is_active' => true]);
        $title = LibraryTitle::create(['school_id' => $school->id, 'genre_id' => $genre->id, 'collection_type' => 'referensi', 'title' => 'Kitab Halaman', 'is_active' => true]);
        LibraryItem::create(['library_title_id' => $title->id, 'library_unit_id' => $unit->id, 'inventory_code' => 'PAGE-001', 'shelf_location' => 'Rak A', 'status' => 'available', 'condition' => 'baik']);

        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/racks?unit_id={$unit->id}")->assertOk()->assertSee('Rak A');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/reports?unit_id={$unit->id}")->assertOk()->assertSee('Laporan');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/fines?unit_id={$unit->id}")->assertOk()->assertSee('Denda');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/catalog?unit_id={$unit->id}&collection=reference")->assertOk()->assertSee('Kitab Halaman');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/programs/procurement?unit_id={$unit->id}")->assertOk()->assertSee('Pengadaan');

        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/programs/procurement", [
            'library_unit_id' => $unit->id,
            'title' => 'Pengadaan Buku Uji',
            'category' => 'Referensi',
            'status' => 'submitted',
            'event_date' => now()->toDateString(),
            'quantity' => 5,
            'amount' => 100000,
        ])->assertRedirect();
        $this->assertDatabaseHas('library_programs', ['library_unit_id' => $unit->id, 'type' => 'procurement', 'title' => 'Pengadaan Buku Uji']);

        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/programs/literacy?unit_id={$unit->id}&category=Resensi%20Buku")->assertOk()->assertSee('Resensi');
    }

    public function test_tanpa_unit_perpustakaan_diarahkan_ke_pengaturan_unit(): void
    {
        $admin = User::create(['name' => 'Admin Tanpa Unit', 'email' => 'admin.tanpa.unit@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);

        $this->actingAs($admin)
            ->get("/{$admin->id}/perpustakaan/catalog")
            ->assertRedirect(route('user.perpustakaan.units.index', ['userId' => $admin->id]));
    }
}
