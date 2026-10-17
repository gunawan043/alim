<?php

namespace Database\Seeders;

use App\Models\AdditionalAssignment;
use App\Models\JenisGtk;
use App\Models\StructuralPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JenisGtkSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        StructuralPosition::query()->delete();
        if (class_exists(AdditionalAssignment::class)) {
            AdditionalAssignment::query()->delete();
        }
        JenisGtk::query()->delete();

        $data = [
            // ── 1. Super Admin ──────────────────────────────────────
            [
                'nama' => 'Super Admin / System Engineer',
                'urutan' => 1,
                'deskripsi' => 'Akses penuh seluruh modul, manajemen database, role-permission, dan sistem log.',
                'role' => 'Super Admin',
                'jabatan' => [
                    'Super Admin',
                    'System Engineer',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 2. Pimpinan ─────────────────────────────────────────
            [
                'nama' => 'Pimpinan Pesantren',
                'urutan' => 2,
                'deskripsi' => 'Executive Dashboard, persetujuan kebijakan strategis, laporan keuangan global, dan otorisasi akhir.',
                'role' => 'Pimpinan',
                'jabatan' => [
                    'Mudir',
                    'Pengasuh Pesantren',
                    'Wadir 1',
                    'Wadir 2',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 3. Satuan Pendidikan ────────────────────────────────
            // Mencakup: KBM, kepsek, wakasek, guru, DAN administrasi sekolah
            [
                'nama' => 'Tenaga Kependidikan Satuan Pendidikan',
                'urutan' => 3,
                'deskripsi' => 'Operasional KBM, presensi mengajar, input nilai mapel, administrasi sekolah, dan kas operasional unit sekolah.',
                'role' => 'Satuan Pendidikan',
                'jabatan' => [
                    // ── Pimpinan Sekolah ──────────────────────────
                    'Kepala Satuan Pendidikan',
                    'Wakil Kepala Satuan Pendidikan',
                    'Wakasek Satuan Pendidikan',

                    // ── Guru ─────────────────────────────────────
                    'Guru Umum',
                    'Guru Agama',
                    'Guru Hadits',
                    'Guru Bahasa Arab',
                    'Guru Tahfidz',
                    'Guru Kelas',

                    // ── Administrasi Sekolah ─────────────────────
                    'Kepala Tata Usaha',
                    'Kepala TU Sekolah',
                    'Tata Usaha',
                    'TU Sekolah',
                    'Staf Tata Usaha',
                    'Staf TU Sekolah',
                    'Bendahara Sekolah',
                    'Kasir Sekolah',
                    'Operator Sekolah',
                ],
                'tugas_tambahan' => [
                    'Wali Kelas',
                    'Koordinator Guru Umum',
                    'Koordinator Guru Agama',
                    'Koordinator Guru Hadits',
                    'Koordinator Guru Bahasa Arab',
                    'Koordinator Guru Tahfidz',
                    'Koordinator Ekstrakurikuler',
                    'Koordinator Kurikulum',
                    'Koordinator Kesiswaan',
                    'Koordinator Laboratorium',
                    'Pembina Ekstrakurikuler',
                    'Tim Kurikulum',
                    'Tim Kesiswaan',
                    'Koordinator Sarpras Satuan Pendidikan',
                ],
            ],

            // ── 4. Asrama ───────────────────────────────────────────
            // Mencakup: pengasuh + TU Asrama + Staf Perizinan
            [
                'nama' => 'Tenaga Pengasuhan & Keasramaan',
                'urutan' => 4,
                'deskripsi' => 'Kepengasuhan santri, presensi ibadah/kegiatan malam, tata tertib asrama, perizinan keluar/pulang, dan plotting kamar.',
                'role' => 'Asrama',
                'jabatan' => [
                    // ── Pengasuh ─────────────────────────────────
                    'Kepala Asrama',
                    'Wakil Kepala Asrama',
                    'Musrif',
                    'Musrifah',
                    'Musyrif',
                    'Musyrifah',

                    // ── Administrasi Asrama ──────────────────────
                    'Tata Usaha Asrama',
                    'TU Asrama',
                    'Staf TU Asrama',
                    'Staf Perizinan',
                    'Perizinan',
                ],
                'tugas_tambahan' => [
                    'Wali Kamar',
                    'Wali Asrama',
                    'Staf Asrama',
                    'Staf Penitipan Barang',
                ],
            ],

            // ── 5. UKS ──────────────────────────────────────────────
            [
                'nama' => 'Tenaga Kesehatan UKS',
                'urutan' => 5,
                'deskripsi' => 'Layanan medis harian, rekam medis santri, rawat inap UKS, inventaris obat, dan rujukan rumah sakit.',
                'role' => 'UKS',
                'jabatan' => [
                    'Kepala UKS',
                    'Staf UKS Putra',
                    'Staf UKS Putri',
                ],
                'tugas_tambahan' => [
                    'Admin UKS Putra',
                    'Admin UKS Putri',
                ],
            ],

            // ── 6. Departemen Tahfidz ───────────────────────────────
            // Mencakup: pengelola + TU Departemen Tahfidz
            [
                'nama' => 'Departemen Tahfidz',
                'urutan' => 6,
                'deskripsi' => 'Data Aggregator & Penjamin Mutu Hafalan: rekapitulasi mutaba\'ah harian, target hafalan, ujian marhalah/tasmi\', dan syahadah.',
                'role' => 'Departemen Tahfidz',
                'jabatan' => [
                    // ── Pengelola ────────────────────────────────
                    'Kepala Departemen Tahfidz',
                    'Wakil Kepala Departemen Tahfidz',

                    // ── Administrasi ─────────────────────────────
                    'Tata Usaha Departemen Tahfidz',
                    'TU Departemen Tahfidz',
                    'Staf TU Departemen Tahfidz',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 7. Departemen Bahasa ────────────────────────────────
            // Mencakup: pengelola + TU Departemen Bahasa
            [
                'nama' => 'Departemen Bahasa',
                'urutan' => 7,
                'deskripsi' => 'Data Aggregator Kebahasaan: jadwal mufrodat, pencatatan pelanggaran bahasa, mahkamah bahasa, dan ujian lisan.',
                'role' => 'Departemen Bahasa',
                'jabatan' => [
                    // ── Pengelola ────────────────────────────────
                    'Kepala Departemen Bahasa',
                    'Wakil Kepala Departemen Bahasa',

                    // ── Administrasi ─────────────────────────────
                    'Tata Usaha Departemen Bahasa',
                    'TU Departemen Bahasa',
                    'Staf TU Departemen Bahasa',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 8. Perpustakaan ─────────────────────────────────────
            [
                'nama' => 'Tenaga Perpustakaan',
                'urutan' => 8,
                'deskripsi' => 'Transaksi sirkulasi (pinjam/kembali), katalog buku/kitab (OPAC), data anggota, denda, dan stock opname.',
                'role' => 'Perpustakaan',
                'jabatan' => [
                    'Koordinator Perpustakaan',
                    'Kepala Perpustakaan',
                    'Staf Perpustakaan',
                    'Pustakawan',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 9. Satuan Keamanan ──────────────────────────────────
            [
                'nama' => 'Tenaga Keamanan & Ketertiban',
                'urutan' => 9,
                'deskripsi' => 'Pos gerbang utama, digital log tamu/kendaraan, verifikasi surat izin outing, patroli ronda, dan log insiden.',
                'role' => 'Satuan Keamanan',
                'jabatan' => [
                    'Kepala Satuan Keamanan',
                    'Koordinator Satuan Keamanan',
                    'Anggota Satuan Keamanan',
                    'Petugas Keamanan',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 10. Humas Personalia ────────────────────────────────
            // Mencakup: Humas + Personalia (SDM)
            [
                'nama' => 'Humas & Personalia',
                'urutan' => 10,
                'deskripsi' => 'Manajemen SDM/GTK (arsip SK, presensi GTK, KPI/evaluasi) serta layanan informasi/aduan publik & wali santri.',
                'role' => 'Humas Personalia',
                'jabatan' => [
                    // ── Humas ────────────────────────────────────
                    'Kepala Humas & Personalia',
                    'Kepala Humas',
                    'Staf Humas',

                    // ── Personalia ───────────────────────────────
                    'Kepala Personalia',
                    'Staf Personalia',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 11. Unit Rumah Tangga ───────────────────────────────
            // Mencakup: sarpras fisik, kebersihan, transportasi, DAN TU URT
            [
                'nama' => 'Tenaga Rumah Tangga & Sarpras',
                'urutan' => 11,
                'deskripsi' => 'Pemeliharaan fisik bangunan, work order ticketing, inventaris aset umum, gudang material URT, dan armada kendaraan.',
                'role' => 'Unit Rumah Tangga',
                'jabatan' => [
                    // ── Kepemimpinan ─────────────────────────────
                    'Kepala Unit Rumah Tangga',
                    'Koordinator Sarpras',
                    'Koordinator Sarana Prasarana',

                    // ── Administrasi ─────────────────────────────
                    'Tata Usaha URT',
                    'TU URT',
                    'Staf TU URT',
                    'Staf URT',

                    // ── Operasional ──────────────────────────────
                    'Teknisi Maintenance',
                    'Teknisi',
                    'Petugas Kebersihan',
                    'Janitor',
                    'Driver',
                    'Pengemudi',
                ],
                'tugas_tambahan' => [
                    'Koordinator Sarpras Satuan Pendidikan',
                ],
            ],

            // ── 12. Keuangan ────────────────────────────────────────
            // MURNI KEUANGAN — tidak ada TU Sekolah di sini
            [
                'nama' => 'Tenaga Keuangan & Akuntansi',
                'urutan' => 12,
                'deskripsi' => 'Kasir SPP/tagihan santri (read-only data/mutasi santri), pengajuan kas keluar, payroll GTK, pembukuan, dan laporan keuangan.',
                'role' => 'Keuangan',
                'jabatan' => [
                    // ── Kepemimpinan ─────────────────────────────
                    'Kepala Departemen Keuangan',
                    'Kepala Keuangan',

                    // ── Kasir / Bendahara ────────────────────────
                    'Bendahara Penerimaan',
                    'Kasir SPP',
                    'Bendahara Pengeluaran',

                    // ── Payroll ──────────────────────────────────
                    'Staf Payroll',
                    'Staf Gaji',

                    // ── Akuntansi ────────────────────────────────
                    'Akuntan',
                    'Staf Pembukuan',
                    'Staf Akuntansi',
                    'Staf Keuangan',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 13. Teknologi Informasi ─────────────────────────────
            [
                'nama' => 'Tenaga Teknologi Informasi',
                'urutan' => 13,
                'deskripsi' => 'Infrastructure management, pemeliharaan server/jaringan, akun user, backup data, dan penanganan problem perangkat TI.',
                'role' => 'Teknologi Informasi',
                'jabatan' => [
                    'Kepala Unit TI',
                    'Kepala Unit Teknologi Informasi',
                    'Network & Infrastructure Administrator',
                    'Application & Database Specialist',
                    'IT Support',
                    'Hardware Technician',
                    'Teknisi Jaringan',
                ],
                'tugas_tambahan' => [],
            ],

            // ── 14. Unit Pelayanan Gizi ─────────────────────────────
            [
                'nama' => 'Tenaga Pelayanan Gizi',
                'urutan' => 14,
                'deskripsi' => 'Perencanaan siklus menu santri, stok bahan makanan, jadwal masak/distribusi makan santri & GTK, serta kebersihan dapur.',
                'role' => 'Unit Pelayanan Gizi',
                'jabatan' => [
                    'Kepala Unit Pelayanan Gizi',
                    'Kepala Unit Gizi & Logistik',
                    'Ahli Gizi',
                    'Menu Planner',
                    'Chef',
                    'Staf Dapur',
                    'Staf Logistik Bahan Pangan',
                    'Koordinator Logistik',
                    'Staf Gizi',
                    'Staf Logistik',
                ],
                'tugas_tambahan' => [],
            ],
        ];

        $generateCode = function (string $text): string {
            $slug = Str::slug($text, '_');
            if (strlen($slug) <= 50) return $slug;
            return substr($slug, 0, 44).'_'.substr(md5($slug), 0, 5);
        };

        foreach ($data as $jData) {
            $jenisGtk = JenisGtk::create([
                'id' => Str::uuid(),
                'nama' => $jData['nama'],
                'deskripsi' => $jData['deskripsi'],
                'urutan' => $jData['urutan'],
                'is_active' => true,
            ]);

            $roleId = DB::table('roles')->where('name', $jData['role'])->value('id');
            $domainCode = $this->getDomainCode($jData['role']);
            $domainId = $domainCode
                ? DB::table('domains')->where('code', $domainCode)->value('id')
                : null;

            // Simpan Jabatan ke StructuralPosition
            foreach ($jData['jabatan'] as $order => $jabatanItem) {
                $code = $generateCode($jData['nama'].' '.$jabatanItem);

                StructuralPosition::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $jabatanItem,
                        'level' => 'pondok',
                        'hierarchy_level' => 5,
                        'jenis_gtk_id' => $jenisGtk->id,
                        'kategori' => 'Jabatan',
                        'description' => null,
                        'role_id' => $roleId,
                        'domain_id' => $domainId,
                        'urutan' => $order + 1,
                        'is_active' => true,
                    ]
                );
            }

            // Simpan Tugas Tambahan
            foreach ($jData['tugas_tambahan'] as $order => $tugasItem) {
                $code = $generateCode($jData['nama'].' tt '.$tugasItem);

                if (class_exists(AdditionalAssignment::class)) {
                    AdditionalAssignment::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => $tugasItem,
                            'jenis_gtk_id' => $jenisGtk->id,
                            'urutan' => $order + 1,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ JenisGtkSeeder selesai. 14 kategori, jabatan administrasi tersebar ke unit masing-masing.');
    }

    private function getDomainCode(string $roleName): ?string
    {
        return match ($roleName) {
            'Super Admin'           => 'super_admin',
            'Pimpinan'              => 'pimpinan',
            'Satuan Pendidikan'     => 'satuan_pendidikan',
            'Asrama'                => 'asrama',
            'UKS'                   => 'uks',
            'Departemen Tahfidz'    => 'departemen_tahfidz',
            'Departemen Bahasa'     => 'departemen_bahasa',
            'Perpustakaan'          => 'perpustakaan',
            'Satuan Keamanan'       => 'satuan_keamanan',
            'Humas Personalia'      => 'humas_personalia',
            'Unit Rumah Tangga'     => 'unit_rumah_tangga',
            'Keuangan'              => 'keuangan',
            'Teknologi Informasi'   => 'teknologi_informasi',
            'Unit Pelayanan Gizi'   => 'unit_pelayanan_gizi',
            default                 => null,
        };
    }
}