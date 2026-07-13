<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Models\GtkWorkUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. WORK UNIT: Unsur Pimpinan (Root / Pondok) ───────────────────────
        $pondok = WorkUnit::firstOrCreate(
            ['code' => 'PAH-UPI-001'],
            [
                'name' => 'Pondok Abu Hurairah Mataram',
                'type' => 'Unsur Pimpinan',
                'is_active' => true,
            ]
        );
        $this->command->info("✅ Unsur Pimpinan: {$pondok->name} [{$pondok->id}]");

        // ── 2. USER: Para Pimpinan (GTK) ─────────────────────────────────────
        $pimpinans = [
            'muh_husnul_fikri'    => ['name' => 'Muh. Husnul Fikri, M. Pd.',    'jabatan' => 'Mudir / Pimpinan'],
            'budiman'             => ['name' => 'Budiman, S. Pd.',               'jabatan' => 'Pimpinan'],
            'muhammad_sidik'     => ['name' => 'Muhammad Sidik, M. Pd.',       'jabatan' => 'Pimpinan'],
            'munawar'             => ['name' => 'Munawar, M. Pd.',               'jabatan' => 'Pimpinan'],
            'gunawan_trianto'     => ['name' => 'Gunawan Trianto, M. Pd.',       'jabatan' => 'Pimpinan'],
            'muh_saleh_sukiman'   => ['name' => 'Muhammad Saleh Sukiman, M. Pd.', 'jabatan' => 'Pimpinan'],
            'muh_abdul_maad'      => ['name' => "Muhammad Abdul Ma'ad, S. Pd.",  'jabatan' => 'Pimpinan'],
            'ahmad_firdaus'       => ['name' => 'Ahmad Firdaus, Lc.',            'jabatan' => 'Pimpinan'],
            'lalu_wirabuana'      => ['name' => 'Lalu Wirabuana, Lc, M.H.',      'jabatan' => 'Pimpinan'],
        ];

        $userMap = [];
        foreach ($pimpinans as $key => $data) {
            $email = $key . '@abuhurairah.id';
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $data['name'], 'password' => bcrypt('password123'), 'is_active' => true]
            );
            $user->syncRoles(['GTK']);
            if (!GtkWorkUnit::where('user_id', $user->id)->where('work_unit_id', $pondok->id)->exists()) {
                GtkWorkUnit::create([
                    'user_id' => $user->id,
                    'work_unit_id' => $pondok->id,
                    'jabatan' => $data['jabatan'],
                    'is_primary' => true,
                ]);
            }
            $userMap[$key] = $user->id;
            $this->command->info("  ✅ User: {$data['name']} <{$email}>");
        }

        // ── 3. WORK UNITS: Unit Akademik ──────────────────────────────────────
        // Schools + PPS
        $unitAkademikMap = [];
        $schoolsWU = [
            'PAH-UAK-001' => 'SD IT Putra Abu Hurairah Mataram',
            'PAH-UAK-002' => 'SD IT Putri Abu Hurairah Mataram',
            'PAH-UAK-003' => 'SMP IT Putra Abu Hurairah Mataram',
            'PAH-UAK-004' => 'SMP IT Putri Abu Hurairah Mataram',
            'PAH-UAK-005' => 'MA Plus Abu Hurairah Mataram',
            'PAH-UAK-006' => 'SMA IT Putri Abu Hurairah Mataram',
            'PAH-UAK-007' => 'SMA IT Putra Abu Hurairah Mataram',
            'PAH-UAK-008' => 'PPS Abu Hurairah Mataram',
        ];
        foreach ($schoolsWU as $code => $name) {
            $wu = WorkUnit::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => 'Unit Akademik',
                    'parent_id' => $pondok->id,
                    'is_active' => true,
                ]
            );
            $unitAkademikMap[$code] = $wu->id;
            $this->command->info("  ✅ Unit Akademik: {$name}");
        }

        // ── 4. WORK UNITS: Unit Penunjang Akademik (Pengasuhan & Departemen) ──
        $unitPenunjangAkademik = [
            // Pengasuhan
            'PAH-PNG-001' => 'Pengasuhan SMP IT Putra Abu Hurairah Mataram',
            'PAH-PNG-002' => 'Pengasuhan SMP IT Putri Abu Hurairah Mataram',
            'PAH-PNG-003' => 'Pengasuhan MA Plus Abu Hurairah Mataram',
            'PAH-PNG-004' => 'Pengasuhan SMA IT Putri Abu Hurairah Mataram',
            'PAH-PNG-005' => 'Pengasuhan PPS Abu Hurairah Mataram',
            // Departemen
            'PAH-DEP-001' => 'Departemen Tahfizh',
            'PAH-DEP-002' => 'Departemen Bahasa',
        ];
        foreach ($unitPenunjangAkademik as $code => $name) {
            $wu = WorkUnit::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => 'Unit Penunjang Akademik',
                    'parent_id' => $pondok->id,
                    'is_active' => true,
                ]
            );
            $this->command->info("  ✅ Unit Penunjang Akademik: {$name}");
        }

        // ── 5. WORK UNITS: Unit Administrasi ──────────────────────────────────
        $unitAdministrasi = [
            'PAH-ADM-001' => 'Humas dan Personalia',
            'PAH-ADM-002' => 'Keuangan',
            'PAH-ADM-003' => 'Unit Rumah Tangga',
            'PAH-ADM-004' => 'Unit Gizi Dan Logistik',
            'PAH-ADM-005' => 'Keamanan',
        ];
        foreach ($unitAdministrasi as $code => $name) {
            $wu = WorkUnit::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => 'Unit Administrasi',
                    'parent_id' => $pondok->id,
                    'is_active' => true,
                ]
            );
            $this->command->info("  ✅ Unit Administrasi: {$name}");
        }

        // ── 6. WORK UNITS: Unit Pelayanan ─────────────────────────────────────
        $unitPelayanan = [
            'PAH-LAY-001' => 'Unit Kesehatan Sekolah',
            'PAH-LAY-002' => 'Unit Usaha Pondok',
            'PAH-LAY-003' => 'Unit Sistim Teknologi, Informasi & Jaringan',
            'PAH-LAY-004' => 'Perpustakaan',
            'PAH-LAY-005' => 'Laboratorium',
        ];
        foreach ($unitPelayanan as $code => $name) {
            $wu = WorkUnit::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => 'Unit Pelayanan',
                    'parent_id' => $pondok->id,
                    'is_active' => true,
                ]
            );
            $this->command->info("  ✅ Unit Pelayanan: {$name}");
        }

        // ── 7. SCHOOLS: Data sekolah formal ──────────────────────────────────
        // Geographic: 52=NTB, 5271=KOTA MATARAM, 527102=MATARAM, 5271021012=PUNIA
        $baseAddr  = 'Jalan Majapahit No. 54 B Punia, Mataram';
        $kopAlamat = 'Jalan Majapahit No. 54 B Punia, Mataram. Telp. (0370) 633295 / (0370) 639259';

        $principalMap = [
            'PAH-UAK-001' => ['user' => 'muh_husnul_fikri',  'name' => 'Muh. Husnul Fikri, M. Pd.'],
            'PAH-UAK-002' => ['user' => 'budiman',             'name' => 'Budiman, S. Pd.'],
            'PAH-UAK-003' => ['user' => 'muhammad_sidik',    'name' => 'Muhammad Sidik, M. Pd.'],
            'PAH-UAK-004' => ['user' => 'munawar',             'name' => 'Munawar, M. Pd.'],
            'PAH-UAK-005' => ['user' => 'gunawan_trianto',    'name' => 'Gunawan Trianto, M. Pd.'],
            'PAH-UAK-006' => ['user' => 'muh_saleh_sukiman',  'name' => 'Muhammad Saleh Sukiman, M. Pd.'],
            'PAH-UAK-007' => ['user' => 'muh_abdul_maad',    'name' => "Muhammad Abdul Ma'ad, S. Pd."],
        ];

        $schoolEntries = [
            [
                'wu' => 'PAH-UAK-001',
                'name' => 'SD IT Putra Abu Hurairah Mataram',
                'npsn' => '52010101', 'nss' => '527101001',
                'level' => 'sd', 'status' => 'swasta', 'gender' => 'putra',
                'acc' => 'A', 'accYear' => 2024,
                'email' => 'sdputra@abuhurairah.id', 'web' => 'https://sdputra.abuhurairah.id',
                'est' => '2000-07-10', 'decree' => 'SK. DEPAG/2000/01',
                'land' => 5000, 'build' => 3500,
                'bank' => 'Bank NTB', 'cabang' => 'Mataram', 'rek' => '0189012345678901',
                'npwp' => '52.001.234.5-000.001',
            ],
            [
                'wu' => 'PAH-UAK-002',
                'name' => 'SD IT Putri Abu Hurairah Mataram',
                'npsn' => '52010102', 'nss' => '527101002',
                'level' => 'sd', 'status' => 'swasta', 'gender' => 'putri',
                'acc' => 'A', 'accYear' => 2024,
                'email' => 'sdputri@abuhurairah.id', 'web' => 'https://sdputri.abuhurairah.id',
                'est' => '2000-07-10', 'decree' => 'SK. DEPAG/2000/02',
                'land' => 4500, 'build' => 3000,
                'bank' => 'Bank NTB', 'cabang' => 'Mataram', 'rek' => '0189012345678902',
                'npwp' => '52.001.234.5-000.002',
            ],
            [
                'wu' => 'PAH-UAK-003',
                'name' => 'SMP IT Putra Abu Hurairah Mataram',
                'npsn' => '52010203', 'nss' => '527102001',
                'level' => 'smp', 'status' => 'swasta', 'gender' => 'putra',
                'acc' => 'A', 'accYear' => 2023,
                'email' => 'smpputra@abuhurairah.id', 'web' => 'https://smpputra.abuhurairah.id',
                'est' => '2003-07-15', 'decree' => 'SK. DEPAG/2003/03',
                'land' => 6000, 'build' => 4200,
                'bank' => 'Bank NTB', 'cabang' => 'Mataram', 'rek' => '0189012345678903',
                'npwp' => '52.001.234.5-000.003',
            ],
            [
                'wu' => 'PAH-UAK-004',
                'name' => 'SMP IT Putri Abu Hurairah Mataram',
                'npsn' => '52010204', 'nss' => '527102002',
                'level' => 'smp', 'status' => 'swasta', 'gender' => 'putri',
                'acc' => 'A', 'accYear' => 2023,
                'email' => 'smpputri@abuhurairah.id', 'web' => 'https://smpputri.abuhurairah.id',
                'est' => '2003-07-15', 'decree' => 'SK. DEPAG/2003/04',
                'land' => 5500, 'build' => 3800,
                'bank' => 'Bank NTB', 'cabang' => 'Mataram', 'rek' => '0189012345678904',
                'npwp' => '52.001.234.5-000.004',
            ],
            [
                'wu' => 'PAH-UAK-005',
                'name' => 'MA Plus Abu Hurairah Mataram',
                'npsn' => '52010305', 'nss' => '527103001',
                'level' => 'sma', 'status' => 'swasta', 'gender' => 'putri',
                'acc' => 'A', 'accYear' => 2022,
                'email' => 'ma@abuhurairah.id', 'web' => 'https://ma.abuhurairah.id',
                'est' => '2006-07-10', 'decree' => 'SK. DEPAG/2006/05',
                'land' => 7000, 'build' => 5000,
                'bank' => 'Bank NTB', 'cabang' => 'Mataram', 'rek' => '0189012345678905',
                'npwp' => '52.001.234.5-000.005',
            ],
            [
                'wu' => 'PAH-UAK-006',
                'name' => 'SMA IT Putri Abu Hurairah Mataram',
                'npsn' => '52010306', 'nss' => '527103002',
                'level' => 'sma', 'status' => 'swasta', 'gender' => 'putri',
                'acc' => 'A', 'accYear' => 2022,
                'email' => 'smaitputri@abuhurairah.id', 'web' => 'https://smaitputri.abuhurairah.id',
                'est' => '2009-07-15', 'decree' => 'SK. DEPAG/2009/06',
                'land' => 6500, 'build' => 4500,
                'bank' => 'Bank NTB', 'cabang' => 'Mataram', 'rek' => '0189012345678906',
                'npwp' => '52.001.234.5-000.006',
            ],
            [
                'wu' => 'PAH-UAK-007',
                'name' => 'SMA IT Putra Abu Hurairah Mataram',
                'npsn' => '52010307', 'nss' => '527103003',
                'level' => 'sma', 'status' => 'swasta', 'gender' => 'putra',
                'acc' => 'A', 'accYear' => 2021,
                'email' => 'smaitputra@abuhurairah.id', 'web' => 'https://smaitputra.abuhurairah.id',
                'est' => '2011-07-10', 'decree' => 'SK. DEPAG/2011/07',
                'land' => 7000, 'build' => 4800,
                'bank' => 'Bank NTB', 'cabang' => 'Mataram', 'rek' => '0189012345678907',
                'npwp' => '52.001.234.5-000.007',
            ],
        ];

        $created = 0;
        $updated = 0;
        foreach ($schoolEntries as $e) {
            $pm = $principalMap[$e['wu']] ?? null;
            $data = [
                'work_unit_id'          => $unitAkademikMap[$e['wu']],
                'name'                  => $e['name'],
                'school_gender'         => $e['gender'],
                'npsn'                 => $e['npsn'],
                'nss'                  => $e['nss'],
                'school_level'         => $e['level'],
                'school_status'        => $e['status'],
                'accreditation'        => $e['acc'],
                'accreditation_year'  => $e['accYear'],
                'principal_user_id'   => $pm ? ($userMap[$pm['user']] ?? null) : null,
                'principal_name'       => $pm['name'] ?? null,
                'address'               => $baseAddr,
                'province_code'         => '52',
                'city_code'             => '5271',
                'district_code'        => '527102',
                'village_code'         => '5271021012',
                'postal_code'          => '83115',
                'phone'                => '(0370) 633295',
                'email'                => $e['email'],
                'website'              => $e['web'],
                'operational_hours'    => 'full_day',
                'established_date'     => $e['est'],
                'established_decree'   => $e['decree'],
                'land_area'            => $e['land'],
                'building_area'        => $e['build'],
                'is_active'            => true,
                'kop_nama'             => strtoupper($e['name']),
                'kop_alamat'           => $kopAlamat,
                'kop_telp'             => '(0370) 633295 / (0370) 639259',
                'kop_email'            => $e['email'],
                'kop_npsn'             => $e['npsn'],
                'kopsis_active'        => true,
                'bank_name'            => $e['bank'],
                'bank_cabang'          => $e['cabang'],
                'bank_rekening'        => $e['rek'],
                'bank_an'              => $e['name'],
                'npwp'                 => $e['npwp'],
            ];

            $school = School::where('npsn', $e['npsn'])->first();
            if ($school) {
                $school->update($data);
                $updated++;
            } else {
                School::create($data);
                $created++;
            }
            $this->command->info("  ✅ Sekolah: {$e['name']} (NPSN: {$e['npsn']})");
        }

        $this->command->info("✅ SchoolSeeder selesai — Created: {$created}, Updated: {$updated}");
        $this->command->info("   WorkUnits total: " . WorkUnit::count());
        $this->command->info("   Schools total: " . School::count());
        $this->command->info("   Users (Pimpinan) total: " . User::count());
    }
}
