<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data awal modul Satuan Keamanan.
 *
 * Idempoten: hanya mengisi tabel yang masih kosong, sehingga aman dijalankan
 * berulang dan tidak menimpa data yang sudah diubah pengguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (DB::table('security_zones')->count() === 0) {
            foreach ([
                ['Z-01', 'Area Asrama', 'tinggi', 'Kamar, blok, dan halaman asrama'],
                ['Z-02', 'Area Sekolah', 'sedang', 'Ruang kelas, laboratorium, dan kantor'],
                ['Z-03', 'Area Umum', 'rendah', 'Parkir, masjid, dan jalan lingkungan'],
            ] as [$code, $name, $risk, $desc]) {
                DB::table('security_zones')->insert([
                    'id' => (string) Str::uuid(),
                    'code' => $code,
                    'name' => $name,
                    'risk_level' => $risk,
                    'description' => $desc,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('security_posts')->count() === 0) {
            $zoneId = DB::table('security_zones')->where('code', 'Z-03')->value('id');

            foreach ([
                ['POS-01', 'Pos Gerbang Utama', 'Pintu masuk utama pesantren'],
                ['POS-02', 'Pos Asrama Putra', 'Depan blok asrama putra'],
                ['POS-03', 'Pos Asrama Putri', 'Depan blok asrama putri'],
            ] as [$code, $name, $location]) {
                DB::table('security_posts')->insert([
                    'id' => (string) Str::uuid(),
                    'zone_id' => $zoneId,
                    'code' => $code,
                    'name' => $name,
                    'location' => $location,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('security_shifts')->count() === 0) {
            foreach ([
                ['S1', 'Pagi', '06:00', '14:00'],
                ['S2', 'Siang', '14:00', '22:00'],
                ['S3', 'Malam', '22:00', '06:00'],
            ] as [$code, $name, $start, $end]) {
                DB::table('security_shifts')->insert([
                    'id' => (string) Str::uuid(),
                    'code' => $code,
                    'name' => $name,
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('security_incident_types')->count() === 0) {
            foreach ([
                ['INS-01', 'Orang Asing Masuk', 'berat', 'Orang tidak dikenal berada di area pesantren'],
                ['INS-02', 'Pencurian', 'berat', 'Kehilangan barang milik santri atau pesantren'],
                ['INS-03', 'Perkelahian', 'sedang', 'Perselisihan fisik antar santri'],
                ['INS-04', 'Kerusakan Fasilitas', 'ringan', 'Fasilitas rusak akibat kelalaian'],
                ['INS-05', 'Kebakaran / Bencana', 'kritis', 'Kejadian darurat yang mengancam keselamatan'],
                ['INS-06', 'Pelanggaran Jam Malam', 'sedang', 'Santri berada di luar kamar melewati batas waktu'],
            ] as [$code, $name, $severity, $desc]) {
                DB::table('security_incident_types')->insert([
                    'id' => (string) Str::uuid(),
                    'code' => $code,
                    'name' => $name,
                    'default_severity' => $severity,
                    'description' => $desc,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Data awal tidak dihapus otomatis agar tidak menghapus data yang sudah dipakai.
    }
};
