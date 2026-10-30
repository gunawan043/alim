<?php
/**
 * Import 53 baris GTK dari storage/app/imports/temp_import.xlsx ke dalam tabel GTK.
 * Sebelum dijalankan, pastikan loop `migrate:fresh` sudah dimatikan, dan work unit
 * untuk masing-masing baris sudah diketahui (file xlsx TIDAK memiliki kolom Satuan Kerja).
 */
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$sheet = IOFactory::load(storage_path('app/imports/temp_import.xlsx'))->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);
$hdr = array_shift($rows);
$map = array_flip($hdr); // nama kolam -> huruf kolom Excel

$data = [];
foreach ($rows as $r) {
    if (! empty(trim((string) ($r[$map['name']] ?? '')))) {
        $rec = [];
        foreach ($map as $k => $col) {
            $rec[$k] = $r[$col] ?? null;
        }
        $data[] = $rec;
    }
}

echo "Total baris data: ".count($data)."\n\n";

// Map status perkawinan
$mapStatus = [
    'belum menikah' => 'belum_kawin',
    'menikah' => 'kawin',
    'janda/cerai' => null, // di-resolve berdasarkan jenis kelamin di bawah
];
// Map status kepegawaian
$mapSK = [
    'tetap' => 'PTY',
    'percobaan' => 'Percobaan',
];
$jkMap = ['l' => 'L', 'p' => 'P'];

$inserted = $skipped = 0;
$errors = [];

foreach ($data as $i => $d) {
    $name = trim((string) ($d['name'] ?? ''));
    $email = trim((string) ($d['email'] ?? ''));
    $nik = preg_replace('/\D/', '', (string) ($d['nik'] ?? ''));
    $nupy = preg_replace('/\D/', '', (string) ($d['nupy'] ?? ''));
    $rowNo = $i + 2;

    if ($email === '' || $nik === '' || $nupy === '') {
        $errors[] = "Baris $rowNo ($name): email/NIK/NUPY kosong, dilewati.";
        $skipped++;
        continue;
    }

    // Cek duplikasi
    if (DB::table('users')->where('email', $email)->exists()) {
        $errors[] = "Baris $rowNo ($name): email $email sudah ada, dilewati.";
        $skipped++;
        continue;
    }
    if (DB::table('gtk_profiles')->where('nik', $nik)->exists()) {
        $errors[] = "Baris $rowNo ($name): NIK $nik sudah ada, dilewati.";
        $skipped++;
        continue;
    }
    if (DB::table('gtk_employments')->where('nupy', $nupy)->exists()) {
        $errors[] = "Baris $rowNo ($name): NUPY $nupy sudah ada, dilewati.";
        $skipped++;
        continue;
    }

    $jk = strtoupper(trim((string) ($d['jenis_kelamin'] ?? '')));
    $jk = $jkMap[$jk] ?? null;

    // Status perkawinan
    $sp = strtolower(trim((string) ($d['status_perkawinan'] ?? '')));
    $spMapped = $mapStatus[$sp] ?? null;
    if ($spMapped === null && $sp === 'janda/cerai') {
        $spMapped = ($jk === 'L') ? 'duda_cerai' : 'janda_cerai';
    }

    $tglLahir = $d['tanggal_lahir'] ? \Carbon\Carbon::parse((string) $d['tanggal_lahir'])->format('Y-m-d') : null;
    $tmt = $d['tmt'] ? \Carbon\Carbon::parse((string) $d['tmt'])->format('Y-m-d') : null;
    $tglSK = $d['tanggal_sk'] ? \Carbon\Carbon::parse((string) $d['tanggal_sk'])->format('Y-m-d') : null;

    $npwp = trim((string) ($d['npwp'] ?? ''));
    $npwp = $npwp === '' || $npwp === '-' ? null : $npwp;

    DB::beginTransaction();
    try {
        $userId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('@'.$nupy),
            'is_active' => 1,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_profiles')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'nik' => $nik,
            'no_kk' => preg_replace('/\D/', '', (string) ($d['no_kk'] ?? '')) ?: null,
            'tempat_lahir' => trim((string) ($d['tempat_lahir'] ?? '')) ?: null,
            'tanggal_lahir' => $tglLahir,
            'jenis_kelamin' => $jk,
            'golongan_darah' => strtoupper(trim((string) ($d['golongan_darah'] ?? ''))) ?: null,
            'agama' => null, // kolam kosong di xlsx
            'status_perkawinan' => $spMapped,
            'npwp' => $npwp,
            'jabatan' => trim((string) ($d['jabatan'] ?? '')) ?: null,
            'tmt_kerja' => $tmt,
            'no_hp' => preg_replace('/\D/', '', (string) ($d['no_hp'] ?? '')) ?: null,
            'no_whatsapp' => preg_replace('/\D/', '', (string) ($d['no_whatsapp'] ?? '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_contacts')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'no_hp' => preg_replace('/\D/', '', (string) ($d['no_hp'] ?? '')) ?: null,
            'no_whatsapp' => preg_replace('/\D/', '', (string) ($d['no_whatsapp'] ?? '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'status_kepegawaian' => $mapSK[strtolower(trim((string) ($d['status_kepegawaian'] ?? '')))] ?? null,
            'nupy' => $nupy,
            'satuan_kerja' => null, // xlsx tidak memiliki kolom Satuan Kerja
            'jenis_gtk' => trim((string) ($d['jenis_gtk'] ?? '')) ?: null,
            'jabatan' => trim((string) ($d['jabatan'] ?? '')) ?: null,
            'position_type' => null,
            'is_homeroom' => 0,
            'tmt' => $tmt,
            'nomor_sk' => trim((string) ($d['nomor_sk'] ?? '')) ?: null,
            'decree_number' => null,
            'tanggal_sk' => $tglSK,
            'decree_date' => null,
            'pangkat_golongan' => trim((string) ($d['pangkat_golongan'] ?? '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Pendidikan
        if (trim((string) ($d['jenjang_pendidikan'] ?? '')) !== '' || trim((string) ($d['nama_sekolah'] ?? '')) !== '') {
            DB::table('gtk_educations')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'jenjang_pendidikan' => trim((string) ($d['jenjang_pendidikan'] ?? '')) ?: null,
                'nama_satuan_pendidikan' => trim((string) ($d['nama_sekolah'] ?? '')) ?: null,
                'jurusan' => trim((string) ($d['jurusan'] ?? '')) ?: null,
                'fakultas' => null,
                'tahun_masuk' => null,
                'tahun_lulus' => $d['tahun_lulus'] ? (int) preg_replace('/\D/', '', (string) $d['tahun_lulus']) : null,
                'status' => 'LULUS',
                'is_verified' => 1,
                'verified_at' => now(),
                'verified_by' => null,
                'urutan' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Alamat domisili (dengan kode wilayah Indonesia)
        $jalan = trim((string) ($d['alamat_jalan'] ?? ''));
        $rtRw = trim((string) ($d['alamat_rt_rw'] ?? ''));
        $desa = trim((string) ($d['alamat_desa'] ?? ''));
        $kec = trim((string) ($d['alamat_kecamatan'] ?? ''));
        $kota = trim((string) ($d['alamat_kota'] ?? ''));
        $prov = trim((string) ($d['alamat_provinsi'] ?? ''));
        $kodePos = trim((string) ($d['kode_pos'] ?? '')) ?: null;

        if ($jalan !== '' || $desa !== '' || $kec !== '' || $kota !== '') {
            $provCode = DB::table('indonesia_provinces')->where('name', $prov)->value('code');
            $cityCode = $provCode ? DB::table('indonesia_cities')->where('province_code', $provCode)->where('name', $kota)->value('code') : null;
            $distCode = $cityCode ? DB::table('indonesia_districts')->where('city_code', $cityCode)->where('name', $kec)->value('code') : null;
            $villCode = $distCode ? DB::table('indonesia_villages')->where('district_code', $distCode)->where('name', $desa)->value('code') : null;

            DB::table('gtk_addresses')->insert([
                'id' => (string) Str::uuid(),
                'gtk_profile_id' => null, // diisi setelah profil diketahui — lihat di bawah
                'type' => 'domisili',
                'jalan' => $jalan ?: null,
                'rt_rw' => $rtRw ?: null,
                'dusun' => null,
                'desa' => $desa ?: null,
                'kode_pos' => $kodePos,
                'kecamatan' => $kec ?: null,
                'kab_kota' => $kota ?: null,
                'provinsi' => $prov ?: null,
                'province_code' => $provCode,
                'city_code' => $cityCode,
                'district_code' => $distCode,
                'village_code' => $villCode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();
        $inserted++;
        echo "OK  [$rowNo] $name ($email)\n";
    } catch (\Exception $e) {
        DB::rollBack();
        $errors[] = "Baris $rowNo ($name): ".$e->getMessage();
        $skipped++;
        echo "ERR [$rowNo] $name: ".$e->getMessage()."\n";
    }
}

echo "\n=== SELESAI ===\nBerhasil: $inserted | Dilewati/Gagal: $skipped\n";
if (! empty($errors)) {
    echo "\nDetail error:\n".implode("\n", $errors)."\n";
}
