<?php

namespace App\Services;

use App\Models\GtkProfile;
use App\Models\KontrakKerja;
use App\Models\RecruitmentApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecruitmentConversionService
{
    public function convert(RecruitmentApplication $application, array $data = []): GtkProfile
    {
        $profile = $application->recruitmentProfile;
        $user = $profile->user;

        return DB::transaction(function () use ($application, $profile, $user, $data) {
            // Buat GTK Profile baru
            $gtk = GtkProfile::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'nik' => $profile->nik,
                'nama' => $user->name,
                'jenis_gtk' => $data['jenis_gtk'] ?? 'guru',
                'tempat_lahir' => $profile->tempat_lahir,
                'tanggal_lahir' => $profile->tanggal_lahir,
                'jenis_kelamin' => $profile->jenis_kelamin,
                'agama' => $profile->agama,
                'alamat' => $profile->alamat_lengkap,
                'no_hp' => $profile->no_hp,
                'email' => $user->email,
                'status' => 'aktif',
                'tanggal_masuk' => $data['tanggal_masuk'] ?? now()->toDateString(),
                'status_kepegawaian' => $data['status_kepegawaian'] ?? 'honor',
                'sumber_data' => 'recruitment',
                'recruitment_application_id' => $application->id,
            ]);

            // Update status aplikasi
            $application->update([
                'status' => 'diterima',
                'status_akhir' => 'diterima',
                'diproses_at' => now(),
                'selesai_at' => now(),
            ]);

            // Buat kontrak kerja otomatis
            $this->createContract($gtk, $application, $data);

            return $gtk;
        });
    }

    private function createContract(GtkProfile $gtk, RecruitmentApplication $application, array $data): KontrakKerja
    {
        $job = $application->recruitmentJob;

        return KontrakKerja::create([
            'uuid' => Str::uuid(),
            'gtk_uuid' => $gtk->uuid,
            'school_id' => $data['school_id'] ?? null,
            'nomor_kontrak' => 'KON-'.date('Ymd').'-'.Str::upper(Str::random(4)),
            'jenis' => $data['kontrak_jenis'] ?? 'pkwt',
            'tanggal_mulai' => $data['tanggal_mulai'] ?? now()->toDateString(),
            'tanggal_selesai' => $data['tanggal_selesai'] ?? now()->addMonths(12)->toDateString(),
            'durasi_bulan' => $data['durasi_bulan'] ?? 12,
            'jabatan' => $job->posisi ?? null,
            'unit_kerja' => $job->workUnit?->name ?? null,
            'lokasi_kerja' => $job->lokasi ?? null,
            'gaji_pokok' => $data['gaji_pokok'] ?? null,
            'tunjangan_tetap' => $data['tunjangan_tetap'] ?? null,
            'tunjangan_tidak_tetap' => $data['tunjangan_tidak_tetap'] ?? null,
            'status' => 'draft',
            'dibuat_oleh' => auth()->id(),
            'catatan' => "Kontrak kerja otomatis dari rekrutmen: {$application->no_lamaran}",
        ]);
    }

    public function preview(RecruitmentApplication $application): array
    {
        $profile = $application->recruitmentProfile;
        $user = $profile->user;
        $job = $application->recruitmentJob;

        return [
            'nama' => $user->name,
            'email' => $user->email,
            'nik' => $profile->nik,
            'jenis_kelamin' => $profile->jenis_kelamin,
            'ttl' => "{$profile->tempat_lahir}, {$profile->tanggal_lahir?->format('d M Y')}",
            'pendidikan' => $profile->educations->max('jenjang') ?? '-',
            'posisi' => $job->judul ?? '-',
            'unit' => $job->workUnit?->name ?? '-',
        ];
    }
}
