<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ArchiveDocument;
use App\Services\Arsip\BackupReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * V8 — Keamanan & operasional arsip: verifikasi hash dokumen dan pengingat backup.
 */
class ArsipSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifikasi_dokumen_valid_dan_terdeteksi_saat_dirusak(): void
    {
        Storage::fake('local');

        $document = $this->makeDocument('arsip/test/valid.pdf', 'PDF-CONTENT-VALID');

        $this->artisan('arsip:verifikasi-dokumen')
            ->assertExitCode(0);

        Storage::disk('local')->put($document->file_path, 'TAMPERED-CONTENT');

        $this->artisan('arsip:verifikasi-dokumen')
            ->expectsOutputToContain('HASH TIDAK COCOK')
            ->assertExitCode(1);
    }

    public function test_verifikasi_mendeteksi_berkas_hilang(): void
    {
        Storage::fake('local');

        $document = $this->makeDocument('arsip/test/missing.pdf', 'X');
        Storage::disk('local')->delete($document->file_path);

        $this->artisan('arsip:verifikasi-dokumen')
            ->expectsOutputToContain('BERKAS HILANG')
            ->assertExitCode(1);
    }

    public function test_backup_reminder_mendeteksi_backup_kedaluwarsa(): void
    {
        Storage::fake('local');

        $directory = storage_path('framework/testing/backup-'.Str::lower(Str::random(6)));
        File::ensureDirectoryExists($directory);

        $file = $directory.'/database.sql';
        file_put_contents($file, 'dump');
        touch($file, now()->subDays(10)->timestamp);

        config()->set('arsip.backup.path', $directory);
        config()->set('arsip.backup.max_age_days', 7);

        $service = app(BackupReminderService::class);

        $this->assertTrue($service->isDue());
        $this->assertNotNull($service->latestBackupAt());

        $this->artisan('arsip:pengingat-backup')
            ->expectsOutputToContain('kedaluwarsa')
            ->assertExitCode(0);

        touch($file, now()->timestamp);
        $this->assertFalse($service->isDue());

        File::deleteDirectory($directory);
    }

    private function makeDocument(string $path, string $contents): ArchiveDocument
    {
        Storage::disk('local')->put($path, $contents);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

        return ArchiveDocument::create([
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'type' => 'rapor',
            'title' => 'Dokumen Uji',
            'file_path' => $path,
            'file_hash' => hash('sha256', $contents),
            'file_size' => strlen($contents),
        ]);
    }
}
