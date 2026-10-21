<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssetRoom;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\GtkProfile;
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
use App\Models\StudentOperatorAssignment;
use App\Models\LibraryStockOpname;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\LibraryUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/genres")->assertOk()->assertSee('Genre');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/visits?unit_id={$unit->id}")->assertOk()->assertSee('Kunjungan');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/units")->assertOk()->assertSee('Unit Perpustakaan');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/stock-opnames?unit_id={$unit->id}")->assertOk()->assertSee('Stock Opname');
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

    public function test_seeder_unit_perpustakaan_wajib_dan_idempotent(): void
    {
        $this->seed(LibraryUnitSeeder::class);
        $this->seed(LibraryUnitSeeder::class);

        $this->assertDatabaseCount('library_units', 3);
        $this->assertDatabaseHas('library_units', ['code' => 'PERPUS-PUTRA', 'name' => 'Perpustakaan Putra']);
        $this->assertDatabaseHas('library_units', ['code' => 'PERPUS-PUTRI', 'name' => 'Perpustakaan Putri']);
        $this->assertDatabaseHas('library_units', ['code' => 'PERPUS-SD', 'name' => 'Perpustakaan SD']);
    }

    public function test_import_anggota_dan_katalog_perpustakaan(): void
    {
        $workUnit = WorkUnit::create(['name' => 'Unit Import Perpus', 'code' => 'PERPUS-IMP', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $workUnit->id, 'npsn' => '66666666', 'name' => 'Sekolah Import']);
        $admin = User::create(['name' => 'Admin Import', 'email' => 'admin.import@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $unit = LibraryUnit::create(['work_unit_id' => $workUnit->id, 'school_id' => $school->id, 'code' => 'PERPUS-I', 'name' => 'Perpustakaan Import', 'gender_scope' => 'mixed', 'level_scope' => 'sd_smp', 'is_active' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Import', 'nis' => 'IMP-001', 'nisn' => 'IMP-N-001', 'gender' => 'L', 'status' => 'active']);
        $gtk = User::create(['name' => 'GTK Import', 'email' => 'gtk.import@test.local', 'password' => bcrypt('password'), 'is_active' => true]);

        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/import/members")->assertOk()->assertSee('Import Anggota');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/import/members/template")->assertOk();
        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/import/members", [
            'library_unit_id' => $unit->id,
            'file' => UploadedFile::fake()->createWithContent('anggota.csv', "nis,nisn,nama,email,jenis\nIMP-001,,Santri Import,,santri\n,,GTK Import,gtk.import@test.local,gtk\n"),
        ])->assertRedirect()->assertSessionHas('import_result');
        $this->assertDatabaseHas('library_members', ['library_unit_id' => $unit->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('library_members', ['library_unit_id' => $unit->id, 'user_id' => $gtk->id]);

        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/import/catalog")->assertOk()->assertSee('Import Katalog');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/import/catalog/template")->assertOk();
        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/import/catalog", [
            'library_unit_id' => $unit->id,
            'file' => UploadedFile::fake()->createWithContent('katalog.csv', "judul,penulis,penerbit,tahun,genre,jenis_koleksi,kode_inventaris,rak,kondisi,status\nBuku Import,Penulis Import,Penerbit Import,2026,Referensi,referensi,IMP-BUKU-001,Rak Import,baik,tersedia\n"),
        ])->assertRedirect()->assertSessionHas('import_result');
        $this->assertDatabaseHas('library_titles', ['title' => 'Buku Import', 'school_id' => $school->id]);
        $this->assertDatabaseHas('library_items', ['inventory_code' => 'IMP-BUKU-001', 'library_unit_id' => $unit->id]);

        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/import/genres")->assertOk()->assertSee('Import Genre');
        $this->actingAs($admin)->get("/{$admin->id}/perpustakaan/import/genres/template")->assertOk();
        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/import/genres", [
            'library_unit_id' => $unit->id,
            'file' => UploadedFile::fake()->createWithContent('genre.csv', "nama,kode,deskripsi,status\nGenre Import,G-IMP,Genre dari import,aktif\n"),
        ])->assertRedirect()->assertSessionHas('import_result');
        $this->assertDatabaseHas('library_genres', ['name' => 'Genre Import', 'school_id' => $school->id]);
    }

    public function test_pencarian_anggota_dan_gtk_mengikuti_gender_unit(): void
    {
        $workUnit = WorkUnit::create(['name' => 'Unit Gender Perpus', 'code' => 'PERPUS-GENDER', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $workUnit->id, 'npsn' => '55555555', 'name' => 'Sekolah Gender']);
        $admin = User::create(['name' => 'Admin Gender', 'email' => 'admin.gender@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $unit = LibraryUnit::create(['work_unit_id' => $workUnit->id, 'school_id' => $school->id, 'code' => 'PERPUS-G', 'name' => 'Perpustakaan Putri', 'gender_scope' => 'P', 'level_scope' => 'sd_smp', 'is_active' => true]);
        $putri = Student::create(['school_id' => $school->id, 'name' => 'Santri Putri', 'nis' => 'G-P-001', 'nisn' => 'G-P-N-001', 'gender' => 'P', 'status' => 'active']);
        $putra = Student::create(['school_id' => $school->id, 'name' => 'Santri Putra', 'nis' => 'G-L-001', 'nisn' => 'G-L-N-001', 'gender' => 'L', 'status' => 'active']);
        $gtkPutri = User::create(['name' => 'Guru Putri', 'email' => 'guru.putri@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $gtkPutra = User::create(['name' => 'Guru Putra', 'email' => 'guru.putra@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        GtkProfile::create(['user_id' => $gtkPutri->id, 'jenis_kelamin' => 'P']);
        GtkProfile::create(['user_id' => $gtkPutra->id, 'jenis_kelamin' => 'L']);

        $students = $this->actingAs($admin)->getJson("/{$admin->id}/perpustakaan/members/students?unit_id={$unit->id}&q=santri")->assertOk()->json('results');
        $this->assertSame([$putri->id], collect($students)->pluck('id')->all());

        $gtk = $this->actingAs($admin)->getJson("/{$admin->id}/perpustakaan/members/gtk?unit_id={$unit->id}&q=guru")->assertOk()->json('results');
        $this->assertSame([$gtkPutri->id], collect($gtk)->pluck('id')->all());

        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/members", [
            'library_unit_id' => $unit->id,
            'member_type' => 'gtk',
            'user_id' => $gtkPutra->id,
        ])->assertStatus(422);

        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/members", [
            'library_unit_id' => $unit->id,
            'member_type' => 'gtk',
            'user_id' => $gtkPutri->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('library_members', ['library_unit_id' => $unit->id, 'user_id' => $gtkPutri->id]);
    }

    public function test_petugas_santri_login_nis_dan_akses_terbatas(): void
    {
        $workUnit = WorkUnit::create(['name' => 'Unit Operator Perpus', 'code' => 'PERPUS-OP', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $workUnit->id, 'npsn' => '44444444', 'name' => 'Sekolah Operator']);
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $admin = User::create(['name' => 'Admin Operator', 'email' => 'admin.operator@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $unit = LibraryUnit::create(['work_unit_id' => $workUnit->id, 'school_id' => $school->id, 'code' => 'PERPUS-OP', 'name' => 'Perpustakaan Operator', 'gender_scope' => 'P', 'level_scope' => 'sd_smp', 'is_active' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Operator', 'nis' => 'OP-001', 'nisn' => 'OP-N-001', 'gender' => 'P', 'status' => 'active']);

        $this->actingAs($admin)->post("/{$admin->id}/perpustakaan/operators", [
            'library_unit_id' => $unit->id,
            'student_id' => $student->id,
            'notes' => 'Petugas piket',
        ])->assertRedirect();

        $operator = User::where('username', 'OP-001')->firstOrFail();
        $this->assertTrue($operator->must_change_password);
        $this->assertTrue($operator->hasRole('Operator Perpustakaan Santri'));
        $this->assertDatabaseHas('student_operator_assignments', ['student_id' => $student->id, 'library_unit_id' => $unit->id, 'user_id' => $operator->id, 'is_active' => 1]);

        $this->post('/logout');
        $this->post('/login', ['identity' => 'OP-001', 'password' => 'OP-001'])
            ->assertRedirect(route('user.perpustakaan.index', ['userId' => $operator->id]));

        $this->actingAs($operator)->get("/{$operator->id}/perpustakaan")->assertRedirect(route('password.force'));
        $this->actingAs($operator)->get('/force-password')->assertOk();

        $operator->update(['must_change_password' => false]);
        $this->actingAs($operator)->get("/{$operator->id}/perpustakaan")->assertOk();
        $this->actingAs($operator)->get("/{$operator->id}/perpustakaan/loans?unit_id={$unit->id}")->assertOk();
        $this->actingAs($operator)->get("/{$operator->id}/perpustakaan/visits?unit_id={$unit->id}")->assertOk();
        $this->actingAs($operator)->get("/{$operator->id}/perpustakaan/catalog?unit_id={$unit->id}")->assertForbidden();
        $this->actingAs($operator)->get("/{$operator->id}/ai")->assertForbidden();
    }

    public function test_petugas_santri_asrama_dan_pembatasan_akses(): void
    {
        $workUnit = WorkUnit::create(['name' => 'Unit Asrama Operator', 'code' => 'ASR-OP', 'type' => 'Unit Asrama', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $workUnit->id, 'npsn' => '33333333', 'name' => 'Sekolah Asrama Operator']);
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $admin = User::create(['name' => 'Admin Asrama Operator', 'email' => 'admin.asrama.operator@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $dormitory = Dormitory::create(['work_unit_id' => $workUnit->id, 'school_id' => $school->id, 'name' => 'Asrama Operator', 'gender' => 'putri', 'is_active' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Asrama Operator', 'nis' => 'ASR-001', 'nisn' => 'ASR-N-001', 'gender' => 'P', 'status' => 'active']);
        DormitoryResident::create(['dormitory_id' => $dormitory->id, 'student_id' => $student->id, 'academic_year_id' => $year->id, 'is_active' => true, 'check_in_date' => now()->toDateString()]);

        $this->actingAs($admin)->get("/{$admin->id}/asrama/operator-santri")->assertOk()->assertSee('Petugas Santri Asrama');
        $this->actingAs($admin)->post("/{$admin->id}/asrama/operator-santri", [
            'dormitory_id' => $dormitory->id,
            'student_id' => $student->id,
        ])->assertRedirect();

        $operator = User::where('username', 'ASR-001')->firstOrFail();
        $this->assertTrue($operator->hasRole('Operator Asrama Santri'));
        $this->assertTrue($operator->must_change_password);
        $this->assertDatabaseHas('student_operator_assignments', ['student_id' => $student->id, 'dormitory_id' => $dormitory->id, 'operator_type' => 'asrama', 'is_active' => 1]);

        $this->post('/logout');
        $this->post('/login', ['identity' => 'ASR-001', 'password' => 'ASR-001'])
            ->assertRedirect(route('user.asrama.visits.index', ['userId' => $operator->id, 'asramaUuid' => $dormitory->id]));

        $this->actingAs($operator)->get("/{$operator->id}/asrama/{$dormitory->id}/kunjungan")->assertRedirect(route('password.force'));
        $this->actingAs($operator)->get('/force-password')->assertOk();
        $operator->update(['must_change_password' => false]);
        $this->actingAs($operator)->get("/{$operator->id}/asrama/{$dormitory->id}/kunjungan")->assertOk();
        $this->actingAs($operator)->get("/{$operator->id}/asrama/{$dormitory->id}/penghuni")->assertForbidden();
    }
}
