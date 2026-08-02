<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\GtkAddress;
use App\Models\GtkDocument;
use App\Models\GtkEducation;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\GtkSkill;
use App\Models\GtkTraining;
use App\Models\GtkWorkExperience;
use App\Models\GtkWorkUnit;
use App\Models\GtkWorkUnitHistory;
use App\Models\Jabatan;
use App\Models\JenisGtk;
use App\Models\RecruitmentApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CandidateConversionService
{
    /**
     * Konversi RecruitmentProfile + RecruitmentApplication yang DITERIMA
     * menjadi GtkProfile + data relasi terkait (education, experience, skill, dll).
     */
    public function convert(RecruitmentApplication $application, array $gtkData = []): GtkProfile
    {
        if ($application->status !== 'diterima') {
            throw new \InvalidArgumentException('Hanya pelamar dengan status "diterima" yang dapat dikonversi.');
        }

        return DB::transaction(function () use ($application, $gtkData) {
            $profile = $application->recruitmentProfile;

            // 1. Buat GtkProfile baru
            $gtkId = (string) Str::uuid();
            $gtk = new GtkProfile([
                'id' => $gtkId,
                'user_id' => $profile->user_id,
                'nik' => $profile->nik,
                'no_kk' => $profile->no_kk,
                'tempat_lahir' => $profile->tempat_lahir,
                'tanggal_lahir' => $profile->tanggal_lahir,
                'nama_ibu_kandung' => $profile->nama_ibu_kandung,
                'golongan_darah' => $profile->golongan_darah,
                'jenis_kelamin' => $profile->jenis_kelamin,
                'agama' => $profile->agama,
                'status_perkawinan' => $profile->status_perkawinan,
                'npwp' => $gtkData['npwp'] ?? null,
            ]);
            $gtk->save();

            // 2. Alamat Domisili (jika ada data alamat di profile)
            if ($profile->alamat_lengkap) {
                GtkAddress::create([
                    'gtk_profile_id' => $gtk->id,
                    'type' => 'domisili',
                    'alamat_lengkap' => $profile->alamat_lengkap,
                    'rt_rw' => $profile->rt_rw,
                    'kelurahan_desa' => $profile->kelurahan_desa,
                    'kecamatan' => $profile->kecamatan,
                    'kota_kabupaten' => $profile->kota_kabupaten,
                    'provinsi' => $profile->provinsi,
                    'kode_pos' => $profile->kode_pos,
                ]);
            }

            // 3. Riwayat Pendidikan
            foreach ($profile->educations as $edu) {
                GtkEducation::create([
                    'gtk_profile_id' => $gtk->id,
                    'jenjang' => $edu->jenjang,
                    'nama_sekolah' => $edu->nama_sekolah,
                    'jurusan' => $edu->jurusan,
                    'fakultas' => $edu->fakultas,
                    'tahun_masuk' => $edu->tahun_masuk,
                    'tahun_lulus' => $edu->tahun_lulus,
                    'ipk' => $edu->ipk,
                    'nilai_akhir' => $edu->nilai_akhir,
                    'skala_nilai' => $edu->skala_nilai,
                    'predikat' => $edu->predikat_kelulusan,
                    'no_ijazah' => $edu->no_ijazah,
                    'ijazah_path' => $edu->ijazah_path,
                    'transkrip_path' => $edu->transkrip_path,
                ]);
            }

            // 4. Pengalaman Kerja
            foreach ($profile->workExperiences as $exp) {
                GtkWorkExperience::create([
                    'gtk_profile_id' => $gtkId,
                    'nama_perusahaan' => $exp->nama_perusahaan,
                    'bidang_perusahaan' => $exp->bidang_perusahaan,
                    'jenis_perusahaan' => $exp->jenis_perusahaan,
                    'posisi_terakhir' => $exp->posisi_terakhir,
                    'tanggal_mulai' => $exp->tanggal_mulai,
                    'tanggal_selesai' => $exp->tanggal_selesai,
                    'is_current' => $exp->is_saat_ini,
                    'jobdesc' => $exp->jobdesc,
                    'gaji_terakhir' => $exp->gaji_terakhir,
                    'alasan_keluar' => $exp->alasan_keluar,
                    'nama_atasan' => $exp->nama_atasan,
                    'kontak_atasan' => $exp->kontak_atasan,
                    'paklaring_path' => $exp->paklaring_path,
                ]);
            }

            // 5. Skills
            foreach ($profile->skills as $skill) {
                GtkSkill::create([
                    'gtk_profile_id' => $gtkId,
                    'kategori' => $skill->kategori,
                    'nama_skill' => $skill->nama_skill,
                    'level' => $skill->level,
                    'tahun_pengalaman' => $skill->tahun_pengalaman,
                    'sumber' => $skill->sumber,
                    'sertifikasi_path' => $skill->sertifikasi_path,
                ]);
            }

            // 6. Pelatihan
            foreach ($profile->trainings as $training) {
                GtkTraining::create([
                    'gtk_profile_id' => $gtkId,
                    'jenis' => $training->jenis,
                    'nama_pelatihan' => $training->nama_pelatihan,
                    'penyelenggara' => $training->penyelenggara,
                    'tingkat' => $training->tingkat,
                    'tanggal_mulai' => $training->tanggal_mulai,
                    'tanggal_selesai' => $training->tanggal_selesai,
                    'durasi_jam' => $training->durasi_jam,
                    'sertifikat_path' => $training->sertifikat_path,
                    'no_sertifikat' => $training->no_sertifikat,
                ]);
            }

            // 7. Dokumen
            foreach ($profile->documents as $doc) {
                GtkDocument::create([
                    'gtk_profile_id' => $gtk->id,
                    'jenis_dokumen' => $doc->jenis_dokumen,
                    'nama_dokumen' => $doc->nama_dokumen,
                    'file_path' => $doc->file_path,
                    'file_size' => $doc->file_size,
                    'file_extension' => $doc->file_extension,
                ]);
            }

            // 8. Update application status_akhir + catat gtk_gtk_profile_id
            $application->update([
                'status_akhir' => 'dikirim_ke_gtk',
                'catatan_rekruter' => ($application->catatan_rekruter ?? '').
                    "\n[".now().'] Dikonversi menjadi GTK oleh '.auth()->user()->name,
            ]);

            // 9. Promote user to GTK: assign role, create employment, set initial work unit
            $this->promoteToGtk($profile, $application, $gtkData);

            // 10. Audit log
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'CANDIDATE_CONVERTED_TO_GTK',
                'table_name' => 'gtk_profiles',
                'record_id' => $gtk->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $gtk;
        });
    }

    /**
     * Promote user to GTK: resolve Jabatan by name, create GtkEmployment
     * (observer will sync Spatie roles from jabatan.roles), attach primary work unit.
     * Called from within the convert() transaction.
     */
    protected function promoteToGtk($profile, RecruitmentApplication $application, array $gtkData): void
    {
        $user = $profile->user;
        if (! $user) {
            return;
        }

        $job = $application->recruitmentJob;
        $workUnitId = $gtkData['work_unit_id'] ?? $job?->work_unit_id;

        $jabatanName = $gtkData['jabatan'] ?? $job?->posisi;
        $jabatan = $jabatanName ? Jabatan::where('nama', $jabatanName)->first() : null;

        $jenisGtkId = null;
        if (! empty($gtkData['jenis_gtk'])) {
            $jenisGtkId = JenisGtk::where('nama', $gtkData['jenis_gtk'])->value('id');
        }
        if (! $jenisGtkId && $jabatan) {
            $jenisGtkId = $jabatan->jenis_gtk_id;
        }

        $employmentPayload = [
            'user_id' => $user->id,
            'status_kepegawaian' => $gtkData['status_kepegawaian']
                ?? $job?->status_pegawai
                ?? 'KONTRAK',
            'jabatan' => $jabatan?->nama ?? $jabatanName,
            'jabatan_id' => $jabatan?->id,
            'jenis_gtk_id' => $jenisGtkId,
            'jenis_gtk' => $gtkData['jenis_gtk'] ?? null,
            'satuan_kerja' => $gtkData['satuan_kerja'] ?? null,
            'tmt' => $gtkData['tmt'] ?? now()->toDateString(),
        ];

        $existingEmployment = GtkEmployment::where('user_id', $user->id)->first();
        if ($existingEmployment) {
            $existingEmployment->fill(array_filter($employmentPayload, fn ($v) => $v !== null));
            $existingEmployment->save();
            $employment = $existingEmployment;
        } else {
            $employment = GtkEmployment::create(array_filter($employmentPayload, fn ($v) => $v !== null));
        }

        if ($workUnitId) {
            $existingAssignment = GtkWorkUnit::where('user_id', $user->id)
                ->where('work_unit_id', $workUnitId)
                ->first();

            if (! $existingAssignment) {
                GtkWorkUnit::where('user_id', $user->id)->update(['is_primary' => false]);

                GtkWorkUnit::create([
                    'user_id' => $user->id,
                    'work_unit_id' => $workUnitId,
                    'jabatan' => $employmentPayload['jabatan'] ?? null,
                    'is_primary' => true,
                ]);

                GtkWorkUnitHistory::create([
                    'user_id' => $user->id,
                    'from_work_unit_id' => null,
                    'to_work_unit_id' => $workUnitId,
                    'jabatan' => $employmentPayload['jabatan'] ?? null,
                    'action' => 'ASSIGN',
                    'reason' => 'Initial assignment via candidate conversion',
                    'performed_by' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        }
    }

    /**
     * Cek apakah pelamar sudah pernah dikonversi.
     */
    public function isAlreadyConverted(RecruitmentApplication $application): bool
    {
        return $application->status_akhir === 'dikirim_ke_gtk';
    }

    /**
     * Ambil info preview sebelum konversi (untuk modal konfirmasi).
     */
    public function preview(RecruitmentApplication $application): array
    {
        $profile = $application->recruitmentProfile;

        return [
            'nama' => $profile->user?->name ?? '-',
            'email' => $profile->user?->email ?? '-',
            'no_hp' => $profile->no_hp ?? '-',
            'lowongan' => $application->recruitmentJob?->judul ?? '-',
            'tanggal_lahir' => $profile->tanggal_lahir?->format('d M Y') ?? '-',
            'pendidikan' => $profile->educations->map(fn ($e) => $e->jenjang.' - '.$e->nama_sekolah)->implode(', '),
            'skills_count' => $profile->skills->count(),
            'documents_count' => $profile->documents->count(),
            'already_converted' => $this->isAlreadyConverted($application),
        ];
    }
}
