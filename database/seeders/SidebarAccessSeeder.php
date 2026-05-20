<?php

namespace Database\Seeders;

use App\Models\SidebarAccess;
use Illuminate\Database\Seeder;

class SidebarAccessSeeder extends Seeder
{
    public function run(): void
    {
        // key => [allowed_roles]
        // empty array = all roles can access
        // null / not set = default (all can access)

        $menus = [
            // Super Admin
            'sa'                 => [],
            'sa.users'           => ['Super Admin'],
            'sa.roles'           => ['Super Admin'],
            'sa.permissions'     => ['Super Admin'],
            'sa.audit_logs'     => ['Super Admin'],
            'sa.tokens'          => ['Super Admin'],
            'sa.failed_jobs'    => ['Super Admin'],
            'sa.notifications'   => ['Super Admin'],
            'sa.sidebar_menus'  => ['Super Admin'],
            'sa.password_reset_logs' => ['Super Admin'],
            'sa.system_settings' => ['Super Admin'],

            // Umum
            'notifications'      => [],

            // Admin TU / Waka
            'dashboard'          => [],
            'sekolah'            => [],
            'gtk'                => [],
            'gtk.guru'           => [],
            'gtk.tendik'         => [],
            'peserta_didik'      => [],
            'grade_levels'       => [],
            'study_groups'       => [],
            'rombel'             => [],
            'mutasi_in'          => [],
            'mutasi_out'         => [],
            'pelanggaran'        => [],
            'alumni'             => [],

            // Akademik
            'subjects'           => [],
            'sumatif'           => [],
            'kisi_kisi'         => [],
            'soal_sumatif'      => [],
            'nilai'             => [],
            'nilai_sts'         => [],
            'nilai_sas'         => [],
            'buku_admin_guru'   => [],
            'absensi'           => [],
            'absensi_gtk'       => [],
            'absensi_pd'        => [],
            'prestasi'          => [],
            'prestasi_akademik' => [],
            'hafalan_quran'     => [],
            'hafalan_hadits'    => [],
            'ekskul'            => [],
            'supervisi'         => ['Wakil Kepala Sekolah'],

            // Administrasi
            'jadwal_kbm'        => [],
            'sk_guru'           => [],
            'jadwal_pelajaran'  => [],
            'jam_mengajar'      => [],
            'rekap_pergantian'  => [],
            'surat_menyurat'    => [],
            'surat_keluar'      => [],
            'surat_masuk'       => [],
            'dokumen_iso'       => [],

            // Pendukung
            'agenda_kegiatan'   => [],
            'kaldik'           => [],
            'pekan_efektif'    => [],
            'sarpras'                    => [],
            'sarpras.dashboard'             => [],
            'sarpras.gedung'              => [],
            'sarpras.ruang'               => [],
            'sarpras.aset'                => [],
            'sarpras.peminjaman'          => [],
            'sarpras.pemeliharaan'         => [],
            'sarpras.booking'             => [],
            'sarpras.perpindahan'         => [],
            'sarpras.pengadaan'           => [],
            'sarpras.qr'                  => [],
            'sarpras.laporan'             => [],

            // GTK
            'gtk_buku_admin'    => ['GTK'],
            'gtk_study_groups' => ['GTK'],
            'gtk_nilai'        => ['GTK'],

            // Master Data
            'master_data'       => [],
            'master_data.jenis_gtk'   => [],
            'master_data.jabatan'     => [],
            'master_data.satuan_kerja' => [],

            // GTK Requests
            'gtk_requests'     => [],
            'gtk_requests.index'    => [],
            'gtk_requests.pengadaan' => [],
            'gtk_requests.percobaan'=> [],
            'gtk_requests.status'   => [],
            'approvals'        => [],

            // Recruitment
            'recruitment'      => [],
            'recruitment.jobs'        => [],
            'recruitment.candidates'   => [],
            'recruitment.applications' => [],
            'recruitment.interviews'   => [],
            'recruitment.reports'     => [],
            'recruitment.settings'    => [],

            // Jenjang Karir
            'jenjang_karir'           => [],
            'jenjang_karir.career_path' => [],
            'jenjang_karir.mutasi'    => [],
            'jenjang_karir.promosi'   => [],
            'jenjang_karir.talent'    => [],
            'jenjang_karir.succession' => [],
        ];

        foreach ($menus as $key => $roles) {
            SidebarAccess::updateOrCreate(
                ['menu_key' => $key],
                ['allowed_roles' => $roles]
            );
        }

        // Cleanup keys that no longer exist in config
        $configKeys = array_keys(config('sidebar'));
        SidebarAccess::whereNotIn('menu_key', $configKeys)->delete();
    }
}