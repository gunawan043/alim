<?php

namespace Tests\Feature;

use App\Http\Controllers\Waka\SuratKeluarController;
use App\Http\Controllers\Waka\SuratMasukController;
use App\Models\School;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\SuratTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Tests\TestCase;

/**
 * Surat masuk & keluar: field form sesuai skema DB, CRUD berjalan,
 * dan halaman utama dapat dirender tanpa error kolom.
 */
class SuratMenyuratSmokeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Surat', 'code' => 'UUS',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '66666666',
            'name' => 'Sekolah Uji Surat',
        ]);

        $this->user = User::create([
            'name' => 'Waka Surat',
            'email' => 'waka.surat@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    public function test_surat_masuk_crud_dan_halaman_render(): void
    {
        $controller = app(SuratMasukController::class);

        $controller->store($this->request('POST', [
            'nomor_surat' => '001/SM/2026',
            'tanggal_surat' => '2026-10-01',
            'tanggal_diterima' => '2026-10-02',
            'pengirim' => 'Dinas Pendidikan',
            'perihal' => 'Undangan Rapat',
            'sifat' => 'penting',
            'sifat_penyelesaian' => 'segera',
            'status' => 'baru',
        ]));

        $this->assertDatabaseHas('surat_masuk', [
            'nomor_surat' => '001/SM/2026',
            'school_id' => $this->school->id,
            'tanggal_diterima' => '2026-10-02 00:00:00',
            'status' => 'baru',
        ]);

        $surat = SuratMasuk::firstOrFail();

        // Daftar + detail dapat dirender (menangkap salah nama kolom).
        $index = $controller->index($this->request());
        $this->assertInstanceOf(View::class, $index);
        $this->assertStringContainsString('Daftar Surat Masuk', $index->render());

        $show = $controller->show($this->request(), $surat->id);
        $this->assertStringContainsString('Undangan Rapat', $show->render());

        $edit = $controller->edit($this->request(), $surat->id);
        $this->assertStringContainsString('Simpan Perubahan', $edit->render());

        $controller->update($this->request('PUT', [
            'nomor_surat' => '001/SM/2026',
            'tanggal_surat' => '2026-10-01',
            'tanggal_diterima' => '2026-10-03',
            'pengirim' => 'Dinas Pendidikan',
            'perihal' => 'Undangan Rapat',
            'sifat' => 'penting',
            'sifat_penyelesaian' => 'segera',
            'status' => 'selesai',
        ]), $surat->id);

        $this->assertSame('selesai', $surat->fresh()->status);

        $controller->destroy($this->request('DELETE'), $surat->id);
        $this->assertSoftDeleted('surat_masuk', ['id' => $surat->id]);
    }

    public function test_surat_keluar_crud_dan_halaman_render(): void
    {
        $controller = app(SuratKeluarController::class);

        $controller->store($this->request('POST', [
            'nomor_surat' => '002/SK/2026',
            'tanggal_surat' => '2026-10-04',
            'tanggal_kirim' => '2026-10-05',
            'tujuan' => 'Kantor Kemenag',
            'perihal' => 'Permohonan Izin',
            'sifat' => 'biasa',
            'penandatangan' => 'Kepala Sekolah',
            'jabatan_penandatangan' => 'Kepala Sekolah',
            'status' => 'terkirim',
        ]));

        $this->assertDatabaseHas('surat_keluar', [
            'nomor_surat' => '002/SK/2026',
            'school_id' => $this->school->id,
            'status' => 'terkirim',
        ]);

        $surat = SuratKeluar::firstOrFail();

        $index = $controller->index($this->request());
        $this->assertInstanceOf(View::class, $index);
        $this->assertStringContainsString('Daftar Surat Keluar', $index->render());

        $show = $controller->show($this->request(), $surat->id);
        $this->assertStringContainsString('Permohonan Izin', $show->render());

        $edit = $controller->edit($this->request(), $surat->id);
        $this->assertStringContainsString('Simpan Perubahan', $edit->render());

        $controller->update($this->request('PUT', [
            'nomor_surat' => '002/SK/2026',
            'tanggal_surat' => '2026-10-04',
            'tanggal_kirim' => '2026-10-06',
            'tujuan' => 'Kantor Kemenag',
            'perihal' => 'Permohonan Izin',
            'sifat' => 'biasa',
            'penandatangan' => 'Kepala Sekolah',
            'jabatan_penandatangan' => 'Kepala Sekolah',
            'status' => 'draft',
        ]), $surat->id);

        $this->assertSame('draft', $surat->fresh()->status);

        $controller->destroy($this->request('DELETE'), $surat->id);
        $this->assertSoftDeleted('surat_keluar', ['id' => $surat->id]);
    }

    public function test_nomor_otomatis_template_dan_verifikasi_qr(): void
    {
        $masuk = app(SuratMasukController::class);

        // Nomor kosong → dibuat otomatis.
        $masuk->store($this->request('POST', [
            'nomor_surat' => '',
            'tanggal_surat' => '2026-10-01',
            'tanggal_diterima' => '2026-10-02',
            'pengirim' => 'Dinas Pendidikan',
            'perihal' => 'Undangan Rapat',
            'sifat' => 'biasa',
            'sifat_penyelesaian' => 'biasa',
            'status' => 'baru',
        ]));

        $surat = SuratMasuk::latest('created_at')->firstOrFail();
        $this->assertMatchesRegularExpression('#^\d{3}/SM/[IVX]+/\d{4}$#', $surat->nomor_surat);
        $this->assertNotEmpty($surat->verification_token);

        // Halaman verifikasi publik dapat dibuka.
        $this->get('/verifikasi-surat/'.$surat->verification_token)
            ->assertOk()
            ->assertSee($surat->nomor_surat)
            ->assertSee('Dokumen Terverifikasi');

        // Template surat keluar.
        $template = SuratTemplate::create([
            'school_id' => $this->school->id,
            'jenis' => 'keluar',
            'nama' => 'Undangan Rapat',
            'perihal_template' => 'Undangan Rapat Koordinasi',
            'isi_template' => "Assalamu'alaikum,
Dengan hormat, kami mengundang Bapak/Ibu.",
            'is_active' => true,
        ]);

        $keluar = app(SuratKeluarController::class);
        $keluar->store($this->request('POST', [
            'nomor_surat' => '',
            'tanggal_surat' => '2026-10-04',
            'tanggal_kirim' => '2026-10-05',
            'tujuan' => 'Kantor Kemenag',
            'perihal' => 'Undangan Rapat Koordinasi',
            'isi_surat' => $template->isi_template,
            'sifat' => 'biasa',
            'penandatangan' => 'Kepala Sekolah',
            'jabatan_penandatangan' => 'Kepala Sekolah',
            'status' => 'terkirim',
        ]));

        $suratKeluar = SuratKeluar::latest('created_at')->firstOrFail();
        $this->assertMatchesRegularExpression('#^\d{3}/SK/[IVX]+/\d{4}$#', $suratKeluar->nomor_surat);
        $this->assertSame($template->isi_template, $suratKeluar->isi_surat);

        // Cetak surat keluar (dokumen + QR verifikasi).
        $print = $keluar->cetak($this->request(), $suratKeluar->id);
        $html = $print->render();
        $this->assertStringContainsString('Undangan Rapat Koordinasi', $html);
        $this->assertStringContainsString('verifikasi-surat/'.$suratKeluar->verification_token, $html);
        $this->assertStringContainsString('<svg', $html);
    }

    private function request(string $method = 'GET', array $data = []): Request
    {
        $request = Request::create('/waka/surat', $method, $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->user);

        return $request;
    }
}
