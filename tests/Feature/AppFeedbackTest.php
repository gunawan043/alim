<?php

namespace Tests\Feature;

use App\Models\AppFeedbackReport;
use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\AppFeedbackController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $reporter;

    private User $otherReporter;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Laporan',
            'code' => 'UUL',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '90909090',
            'name' => 'Sekolah Uji Laporan',
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        $this->reporter = $this->makeUser('Pelapor Satu', 'pelapor1@test.local', $role);
        $this->otherReporter = $this->makeUser('Pelapor Dua', 'pelapor2@test.local', $role);
        $this->manager = $this->makeUser('Pengelola Laporan', 'pengelola@test.local', $role, true);
    }

    public function test_user_dapat_mengirim_laporan_dengan_proses_dan_bukti(): void
    {
        Storage::fake('local');
        $this->actingAs($this->reporter);

        $request = $this->makeRequest($this->reporter, [
            'category' => 'bug',
            'priority' => 'tinggi',
            'title' => 'Tombol simpan tidak bekerja',
            'description' => 'Tombol simpan tidak merespons.',
            'process' => 'Buka nilai kelas, ubah nilai, lalu klik simpan.',
            'expected' => 'Nilai tersimpan.',
            'page_url' => '/schools/nilai-kelas/1',
        ], [
            'evidence' => [UploadedFile::fake()->image('bukti.png', 640, 480)],
        ]);

        $response = app(AppFeedbackController::class)->store($request, $this->reporter->id);
        $this->assertTrue($response->isRedirect());

        $report = AppFeedbackReport::firstOrFail();
        $this->assertSame(
            route('user.feedback.show', ['userId' => $this->reporter->id, 'id' => $report->id]),
            $response->getTargetUrl()
        );

        $this->assertSame('bug', $report->category);
        $this->assertSame('Tombol simpan tidak bekerja', $report->title);
        $this->assertSame('Buka nilai kelas, ubah nilai, lalu klik simpan.', $report->process);
        $this->assertSame('baru', $report->status);
        $this->assertDatabaseHas('app_feedback_updates', [
            'app_feedback_report_id' => $report->id,
            'type' => 'created',
            'status_to' => 'baru',
        ]);

        $attachment = $report->attachments()->firstOrFail();
        $this->assertSame('bukti.png', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_pelapor_hanya_melihat_laporan_sendiri_sedangkan_pengelola_melihat_semua(): void
    {
        $own = $this->makeReport($this->reporter, 'Laporan Milik Saya');
        $other = $this->makeReport($this->otherReporter, 'Laporan Orang Lain');

        $reporterView = app(AppFeedbackController::class)->index(
            $this->makeRequest($this->reporter),
            $this->reporter->id,
        );
        $reporterReports = $reporterView->getData()['reports'];
        $this->assertTrue($reporterReports->contains(fn ($report) => $report->id === $own->id));
        $this->assertFalse($reporterReports->contains(fn ($report) => $report->id === $other->id));

        $managerView = app(AppFeedbackController::class)->index(
            $this->makeRequest($this->manager),
            $this->manager->id,
        );
        $managerReports = $managerView->getData()['reports'];
        $this->assertTrue($managerReports->contains(fn ($report) => $report->id === $own->id));
        $this->assertTrue($managerReports->contains(fn ($report) => $report->id === $other->id));
        $this->assertTrue($managerView->getData()['canManage']);
    }

    public function test_halaman_laporan_dapat_dirender_untuk_pengelola(): void
    {
        $report = $this->makeReport($this->reporter, 'Laporan Halaman Pengelola');

        $this->actingAs($this->manager)
            ->get(route('user.feedback.index', ['userId' => $this->manager->id]))
            ->assertOk()
            ->assertSee('Semua Laporan Pengguna')
            ->assertSee($report->title);

        $this->actingAs($this->manager)
            ->get(route('user.feedback.show', ['userId' => $this->manager->id, 'id' => $report->id]))
            ->assertOk()
            ->assertSee('Riwayat Proses')
            ->assertSee('Tindak Lanjut');
    }

    public function test_pengelola_dapat_menindaklanjuti_dan_riwayat_proses_tersimpan(): void
    {
        $report = $this->makeReport($this->reporter, 'Laporan Untuk Ditindaklanjuti');
        $this->actingAs($this->manager);

        $response = $this->post(route('user.feedback.status', [
            'userId' => $this->manager->id,
            'id' => $report->id,
        ]), [
            'status' => 'diproses',
            'note' => 'Sedang diperiksa oleh tim teknis.',
        ]);

        $response->assertRedirect();
        $this->assertSame('diproses', $report->fresh()->status);
        $this->assertSame($this->manager->id, $report->fresh()->assigned_to);
        $this->assertDatabaseHas('app_feedback_updates', [
            'app_feedback_report_id' => $report->id,
            'user_id' => $this->manager->id,
            'type' => 'status',
            'status_from' => 'baru',
            'status_to' => 'diproses',
            'message' => 'Sedang diperiksa oleh tim teknis.',
        ]);
    }

    public function test_bukti_tidak_dapat_dibuka_pelapor_lain(): void
    {
        Storage::fake('local');
        $report = $this->makeReport($this->reporter, 'Laporan Dengan Bukti');
        $path = 'feedback/'.$report->id.'/bukti.txt';
        Storage::disk('local')->put($path, 'bukti');
        $attachment = $report->attachments()->create([
            'uploaded_by' => $this->reporter->id,
            'path' => $path,
            'original_name' => 'bukti.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);

        try {
            app(AppFeedbackController::class)->attachment(
                $this->makeRequest($this->otherReporter),
                $this->otherReporter->id,
                $report->id,
                $attachment->id,
            );
            $this->fail('Pelapor lain seharusnya tidak dapat membuka bukti.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    private function makeRequest(User $user, array $data = [], array $files = []): Request
    {
        $request = Request::create('/feedback', $data ? 'POST' : 'GET', $data, [], $files);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function makeUser(string $name, string $email, Role $role, bool $systemAdmin = false): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => $systemAdmin,
        ]);
        $user->assignRole($role);

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
        ]);

        return $user;
    }

    private function makeReport(User $reporter, string $title): AppFeedbackReport
    {
        return AppFeedbackReport::create([
            'school_id' => $this->school->id,
            'submitted_by' => $reporter->id,
            'category' => 'bug',
            'priority' => 'sedang',
            'status' => 'baru',
            'title' => $title,
            'description' => 'Deskripsi laporan.',
            'process' => 'Langkah reproduksi masalah.',
            'division' => 'Guru Umum',
            'jabatan' => 'Guru Umum',
        ]);
    }
}
