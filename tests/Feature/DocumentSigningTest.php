<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\DocumentSignature;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMutationOut;
use App\Models\User;
use App\Services\Documents\DocumentSigningService;
use App\Services\Documents\DocumentVisualService;
use App\Services\Documents\SchoolLeaderService;
use App\Services\Pdf\DocumentPdfService;
use App\Services\SchoolGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentSigningTest extends TestCase
{
    use RefreshDatabase;

    private function school(): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Dokumen', 'code' => 'UDOC',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99999999',
            'name' => 'Sekolah Dokumen',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);
    }

    private function year(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);
    }

    public function test_dokumen_qr_dapat_diverifikasi_publik_tanpa_login(): void
    {
        $school = $this->school();
        $year = $this->year();

        $issued = app(DocumentSigningService::class)->issue([
            'document_type' => 'rapor',
            'document_number' => 'RAPOR-UJI-001',
            'title' => 'Laporan Hasil Belajar Santri',
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'semester' => 'ganjil',
            'subject_type' => 'student',
            'subject_id' => (string) Str::uuid(),
        ], [
            ['name' => 'Kepala Sekolah', 'role' => 'Kepala Satuan Pendidikan', 'signer_order' => 1],
            ['name' => 'Wali Kelas', 'role' => 'Wali Kelas', 'signer_order' => 2],
        ]);

        $this->assertNotEmpty($issued['token']);
        $this->assertStringStartsWith('data:image/png;base64,', $issued['qr_data_uri']);
        $this->assertDatabaseMissing('document_signatures', ['verification_token_hash' => $issued['token']]);
        $this->assertDatabaseHas('document_signatures', [
            'verification_token_hash' => hash('sha256', $issued['token']),
            'status' => 'issued',
        ]);

        $response = $this->get($issued['url']);

        $response->assertOk();
        $response->assertSee('Dokumen Sah', false);
        $response->assertSee('Laporan Hasil Belajar Santri', false);
        $response->assertSee('Kepala Sekolah', false);
        $response->assertSee('Wali Kelas', false);
        $response->assertSee('RAPOR-UJI-001', false);

        $this->assertSame(1, $issued['document']->fresh()->scan_count);
    }

    public function test_qr_dapat_dirender_ke_pdf(): void
    {
        $issued = app(DocumentSigningService::class)->issue([
            'document_type' => 'sk',
            'title' => 'SK Uji QR',
            'school_id' => $this->school()->id,
        ], [
            ['name' => 'Kepala Sekolah', 'role' => 'Kepala'],
        ]);

        $html = '<!doctype html><html><body><h1>Dokumen Uji</h1>'
            .view('documents._qr-footer', ['verification' => $issued])->render()
            .'</body></html>';

        $pdf = app(DocumentPdfService::class)->renderHtml($html);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
        // QR harus benar-benar tertanam sebagai image, bukan sekadar markup.
        $this->assertStringContainsString('/Subtype /Image', $pdf);
    }

    public function test_cetak_surat_mutasi_menghasilkan_pdf_dan_registry_qr(): void
    {
        $school = $this->school();
        $user = User::create([
            'name' => 'Admin Dokumen',
            'email' => 'admin.dokumen@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'name' => 'Santri Mutasi',
            'nisn' => '1234567891',
            'gender' => 'L',
            'status' => 'active',
            'entry_date' => now()->toDateString(),
        ]);

        $mutation = StudentMutationOut::create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'status' => 'approved',
            'out_type' => 'mutation',
            'letter_number' => 'MUT-UJI-001',
            'student_name' => $student->name,
            'student_nisn' => $student->nisn,
            'head_name' => 'Kepala Sekolah',
            'head_title' => 'Kepala Sekolah',
            'established_city' => 'Mataram',
            'established_date' => now()->toDateString(),
            'destination_school_name' => 'Sekolah Tujuan',
        ]);

        $response = $this->actingAs($user)->get(route('user.mutations-out.print', [
            'userId' => $user->id,
            'mutationUuid' => $mutation->id,
        ]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        $this->assertStringContainsString('/Subtype /Image', (string) $response->getContent());
        $this->assertDatabaseHas('document_signatures', [
            'document_type' => 'mutasi',
            'document_number' => 'MUT-UJI-001',
            'status' => 'issued',
        ]);
    }

    public function test_preview_surat_mutasi_menampilkan_ttd_dan_memberi_penanda_qr(): void
    {
        Storage::fake('public');
        $school = $this->school();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Storage::disk('public')->put('schools/ttd_ksp_path/ttd.png', $png);
        Storage::disk('public')->put('schools/stamp_path/stamp.png', $png);
        $school->update([
            'ttd_ksp_path' => 'schools/ttd_ksp_path/ttd.png',
            'stamp_path' => 'schools/stamp_path/stamp.png',
        ]);

        app(DocumentVisualService::class)->updateType($school, 'mutasi', [
            'validation_mode' => 'visual',
            'show_ttd_ksp' => '1',
            'show_stamp' => '1',
        ]);
        $school->save();

        $user = User::create([
            'name' => 'Admin Preview',
            'email' => 'admin.preview@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('user.mutations-in.preview-pdf', ['userId' => $user->id]), [
            'school_id' => $school->id,
            'student_name' => 'Calon Santri',
            'head_name' => 'Kepala Sekolah',
            'head_title' => 'Kepala Sekolah',
        ]);

        $response->assertOk();
        $pdf = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        // TTD dan stempel harus tertanam di preview PDF.
        $this->assertStringContainsString('/Subtype /Image', $pdf);
    }

    public function test_kepala_sekolah_tanpa_school_id_memakai_unit_principal_user_id(): void
    {
        $school = $this->school();
        $user = User::create([
            'name' => 'Kepala Unit',
            'email' => 'kepala.unit@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $school->update(['principal_user_id' => $user->id]);

        $resolved = SchoolGroupService::getUserSchool($user);

        $this->assertNotNull($resolved);
        $this->assertSame($school->id, $resolved->id);
    }

    public function test_nama_kota_dokumen_tidak_memakai_prefiks_kota(): void
    {
        $this->assertSame('Mataram', format_city_name('KOTA MATARAM'));
        $this->assertSame('Lombok Barat', format_city_name('KABUPATEN LOMBOK BARAT'));
    }

    public function test_nama_kepala_sekolah_dan_nupy_diambil_dari_data_gtk_dan_gelar(): void
    {
        $school = $this->school();
        $user = User::create([
            'name' => 'GUNAWAN TRIANTO',
            'email' => 'kepala.gelar@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $school->update(['principal_user_id' => $user->id]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'status_kepegawaian' => 'PTT',
            'nupy' => '19811220031007',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_educations')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'jenjang_pendidikan' => 'S1',
                'nama_satuan_pendidikan' => 'Universitas Uji',
                'jurusan' => 'Pendidikan Kimia',
                'is_aktif' => 1,
                'is_verified' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'jenjang_pendidikan' => 'S2',
                'nama_satuan_pendidikan' => 'Universitas Uji',
                'jurusan' => 'Magister Pendidikan',
                'is_aktif' => 1,
                'is_verified' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $leader = app(SchoolLeaderService::class)->forSchool($school->fresh());

        $this->assertSame('Gunawan Trianto', $leader['name']);
        $this->assertSame('Gunawan Trianto, S.Pd, M.Pd', $leader['display_name']);
        $this->assertSame('19811220031007', $leader['nupy']);
        $this->assertSame('Gunawan Trianto, S.Pd, M.Pd', $school->fresh()->principal_full_name);
        $this->assertSame('19811220031007', $school->fresh()->principal_nupy_display);
    }

    public function test_override_nama_dan_nupy_hanya_untuk_dokumen_tidak_mengubah_gtk(): void
    {
        $school = $this->school();
        $user = User::create([
            'name' => 'GUNAWAN TRIANTO',
            'email' => 'kepala.override@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $school->update(['principal_user_id' => $user->id]);
        $school->update([
            'document_visual_settings' => [
                'leader' => ['name' => 'Gunawan Trianto, S.Pd., M.Pd.', 'nupy' => 'DOC-999'],
            ],
        ]);

        $leader = app(SchoolLeaderService::class)->forSchool($school->fresh());

        $this->assertSame('Gunawan Trianto, S.Pd., M.Pd.', $leader['display_name']);
        $this->assertSame('DOC-999', $leader['nupy']);
        $this->assertSame('GUNAWAN TRIANTO', $user->fresh()->name);
        $this->assertNull($school->fresh()->principal_name);
    }

    public function test_token_palsu_menghasilkan_404_dan_tidak_membocorkan_data(): void
    {
        $response = $this->get(route('documents.verify', ['token' => str_repeat('a', 64)]));

        $response->assertNotFound();
        $response->assertSee('Dokumen tidak terdaftar', false);
    }

    public function test_payload_yang_diubah_menghasilkan_status_integritas_tidak_cocok(): void
    {
        $issued = app(DocumentSigningService::class)->issue([
            'document_type' => 'mutasi',
            'document_number' => 'MUT-TAMPER',
            'title' => 'Surat Pindah Uji',
            'school_id' => $this->school()->id,
        ], [
            ['name' => 'Kepala Sekolah', 'role' => 'Kepala'],
        ]);

        $issued['document']->update(['canonical_payload' => '{"tampered":true}']);

        $this->get($issued['url'])
            ->assertOk()
            ->assertSee('Integritas Tidak Cocok', false)
            ->assertDontSee('Dokumen Sah', false);
    }

    public function test_dokumen_dicabut_tetap_dapat_diverifikasi_dengan_status_dicabut(): void
    {
        $issued = app(DocumentSigningService::class)->issue([
            'document_type' => 'sk',
            'document_number' => 'SK-001',
            'title' => 'Surat Keputusan Uji',
            'school_id' => $this->school()->id,
            'subject_type' => 'institution_decree',
            'subject_id' => (string) Str::uuid(),
        ], [
            ['name' => 'Penandatangan', 'role' => 'Kepala'],
        ]);

        app(DocumentSigningService::class)->revoke($issued['document'], 'Kesalahan data');

        $this->get($issued['url'])
            ->assertOk()
            ->assertSee('Dokumen Dicabut', false)
            ->assertSee('Kesalahan data', false);
    }

    public function test_tampilan_stempel_dan_ttd_dapat_dipilih_per_jenis_dokumen(): void
    {
        $school = $this->school();
        $service = app(DocumentVisualService::class);

        $this->assertTrue($service->settings($school, 'mutasi')['show_ttd_ksp']);
        $this->assertTrue($service->settings($school, 'mutasi')['show_stamp']);

        $service->update($school, [
            'mutasi' => [
                'show_ttd_ksp' => '0', 'show_stamp' => '1',
                'ttd_width' => 22, 'ttd_height' => 11, 'ttd_margin' => 3,
                'stamp_width' => 28, 'stamp_height' => 28, 'stamp_margin' => 1,
            ],
            'sk' => [
                'show_ttd_ksp' => '1', 'show_stamp' => '0',
                'ttd_width' => 26, 'ttd_height' => 13, 'ttd_margin' => 4,
            ],
        ]);
        $school->save();
        $school->refresh();

        $this->assertFalse($service->settings($school, 'mutasi')['show_ttd_ksp']);
        $this->assertTrue($service->settings($school, 'mutasi')['show_stamp']);
        $this->assertTrue($service->settings($school, 'sk')['show_ttd_ksp']);
        $this->assertFalse($service->settings($school, 'sk')['show_stamp']);
        $this->assertSame(22.0, $service->settings($school, 'mutasi')['ttd_width']);
        $this->assertSame(11.0, $service->settings($school, 'mutasi')['ttd_height']);
        $this->assertSame(3.0, $service->settings($school, 'mutasi')['ttd_margin']);
        $this->assertSame(28.0, $service->settings($school, 'mutasi')['stamp_width']);
        $this->assertSame(4.0, $service->settings($school, 'sk')['ttd_margin']);

        // Mode QR/PAdES tidak mencetak TTD/stempel.
        $service->updateType($school, 'mutasi', [
            'validation_mode' => 'qr', 'show_ttd_ksp' => '1', 'show_stamp' => '1',
        ]);
        $this->assertFalse($service->viewData($school, 'mutasi')['show_ttd_ksp']);
        $this->assertFalse($service->viewData($school, 'mutasi')['show_stamp']);

        $service->updateType($school, 'mutasi', [
            'validation_mode' => 'visual', 'show_ttd_ksp' => '1', 'show_stamp' => '1',
        ]);
        $this->assertTrue($service->viewData($school, 'mutasi')['show_ttd_ksp']);
        $this->assertTrue($service->viewData($school, 'mutasi')['show_stamp']);
    }

    public function test_mode_pades_ditolak_saat_sertifikat_belum_dikonfigurasi(): void
    {
        config()->set('documents.pades.enabled', false);

        $this->expectException(\RuntimeException::class);

        app(DocumentSigningService::class)->issue([
            'document_type' => 'sk',
            'title' => 'SK PAdES',
            'validation_mode' => 'pades',
        ], [
            ['name' => 'Penandatangan', 'role' => 'Kepala'],
        ]);
    }

    public function test_payload_dan_signature_konsisten_dengan_data_registry(): void
    {
        $issued = app(DocumentSigningService::class)->issue([
            'document_type' => 'mutasi',
            'document_number' => 'MUT-001',
            'title' => 'Surat Pindah Santri',
            'school_id' => $this->school()->id,
        ], [
            ['name' => 'Kepala Sekolah', 'role' => 'Kepala'],
        ]);

        $document = DocumentSignature::findOrFail($issued['document']->id);

        $this->assertSame(hash('sha256', $document->canonical_payload), $document->payload_hash);
        $this->assertSame(
            hash_hmac('sha256', $document->canonical_payload, (string) config('app.key')),
            $document->signature,
        );
        $this->assertSame('visual', $document->signers->first()->signature_status);
    }
}
