<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\GtkProfile;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\GtkContact;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GtkDummySeeder extends Seeder
{
    /**
     * Relasi tabel yang dipakai:
     *
     *   users ────────────── (id, name, email, password)
     *       │
     *       └─── gtk_profiles       (user_id, nik*, tempat_lahir, tanggal_lahir,
     *       │                         jenis_kelamin, agama, status_perkawinan, npwp*)
     *       │
     *       ├─── gtk_employments    (user_id, satuan_kerja="SD Putra",
     *       │                         status_kepegawaian, jenis_gtk, jabatan, tmt)
     *       │
     *       └─── gtk_work_unit      (user_id, work_unit_id → SD IT Putra,
     *                                 jabatan, is_primary)
     *
     *   work_units ── gtk_work_unit.work_unit_id
     *   gtk_employments.satuan_kerja → string "SD Putra" (sesuai instruksi user)
     */

    public function run(): void
    {
        // ── Ambil WorkUnit & School SD IT Putra Abu Hurairah Mataram ───────
        $workUnit = WorkUnit::where('name', 'like', '%SD IT Putra Abu Hurairah Mataram%')->first();
        if (!$workUnit) {
            $this->command->error('❌ WorkUnit SD IT Putra Abu Hurairah Mataram tidak ditemukan. Pastikan SchoolSeeder sudah dijalankan.');
            return;
        }
        $this->command->info("WorkUnit : {$workUnit->name} [{$workUnit->id}]");

        // School untuk relasi gtk_employments.school_id
        $school = \App\Models\School::where('name', 'like', '%SD IT Putra Abu Hurairah Mataram%')->first();
        if (!$school) {
            $this->command->warn('⚠️ School SD IT Putra Abu Hurairah Mataram tidak ditemukan — school_id di gtk_employment dibiarkan null.');
        } else {
            $this->command->info("School   : {$school->name} [{$school->id}]");
        }

        // ── Role GTK ────────────────────────────────────────────────────────
        $roleId = DB::table('roles')->where('name', 'GTK')->value('id');
        if (!$roleId) {
            $this->command->error('❌ Role GTK tidak ditemukan.');
            return;
        }

        // ── Jenis GTK: Tenaga Pendidik Pondok (Guru) ────────────────────────
        $jenisGtk = DB::table('jenis_gtk')->where('nama', 'Tenaga Pendidik Pondok')->value('id');
        if (!$jenisGtk) {
            $this->command->warn('⚠️ JenisGtk "Tenaga Pendidik Pondok" tidak ditemukan — jenis_gtk dibiarkan null.');
        }

        // ── Jabatan: Guru ───────────────────────────────────────────────────
        $jabatanGuru = DB::table('jabatan')->where('nama', 'Guru')->value('id');

        // ── Data dummy 10 guru ──────────────────────────────────────────────
        // Jenis kelamin alternating L/P agar representatif
        $jkList    = ['L', 'P', 'L', 'P', 'L', 'P', 'L', 'P', 'L', 'P'];
        $agamaList = ['islam', 'islam', 'islam', 'islam', 'islam', 'islam', 'islam', 'islam', 'islam', 'islam'];
        // Status perkawinan random agar bervariasi
        $statusKawinList = ['belum_kawin', 'kawin', 'kawin', 'belum_kawin', 'kawin', 'belum_kawin', 'kawin', 'kawin', 'belum_kawin', 'kawin'];
        $golDarahList    = ['A', 'B', 'AB', 'O', 'A', 'O', 'B', 'A', 'AB', 'O'];
        $statusPegList   = ['PTY', 'GTY', 'GTT', 'PTY', 'GTY', 'GTT', 'PTY', 'GTY', 'GTT', 'PTY'];
        // TMT mulai 2018, naik tiap guru 1 tahun
        $tmtYears = [2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025, 2026, 2027];
        // Tempat lahir NTB
        $tempatLahirList = [
            'Mataram', 'Lombok Barat', 'Lombok Tengah', 'Lombok Timur',
            'Lombok Utara', 'Mataram', 'Lombok Barat', 'Mataram',
            'Lombok Timur', 'Mataram',
        ];

        $created = 0;
        $skipped = 0;

        for ($i = 1; $i <= 10; $i++) {
            $email = "guru{$i}@abuhurairah.id";
            $name  = "Guru{$i}";

            // ── 1. User ─────────────────────────────────────────────────────
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => 'password123',
                    'is_active' => true,
                ]);
            }

            // Assign role GTK
            DB::table('model_has_roles')->updateOrInsert(
                ['model_type' => User::class, 'model_id' => $user->id, 'role_id' => $roleId],
                ['role_id' => $roleId]
            );

            // ── 2. GtkProfile ───────────────────────────────────────────────
            if (!GtkProfile::where('user_id', $user->id)->exists()) {
                GtkProfile::create([
                    'user_id'           => $user->id,
                    'nik'               => "52" . str_pad($i, 12, '0', STR_PAD_LEFT),
                    'tempat_lahir'      => $tempatLahirList[$i - 1],
                    'tanggal_lahir'     => now()->subYears(25 + $i)->subMonths(rand(1, 12))->subDays(rand(1, 28))->toDateString(),
                    'nama_ibu_kandung'  => "Ibu Guru{$i}",
                    'golongan_darah'   => $golDarahList[$i - 1],
                    'jenis_kelamin'    => $jkList[$i - 1],
                    'agama'             => $agamaList[$i - 1],
                    'status_perkawinan' => $statusKawinList[$i - 1],
                ]);
            }

            // ── 3. GtkEmployment ────────────────────────────────────────────
            if (!GtkEmployment::where('user_id', $user->id)->exists()) {
                GtkEmployment::create([
                    'user_id'            => $user->id,
                    'school_id'          => $school?->id,
                    'status_kepegawaian' => $statusPegList[$i - 1],
                    'satuan_kerja'       => 'SD Putra',
                    'jenis_gtk'          => 'Tenaga Pendidik Pondok',
                    'jabatan'            => 'Guru',
                    'jenis_gtk_id'       => $jenisGtk,
                    'jabatan_id'         => $jabatanGuru,
                    'tmt'                => "{$tmtYears[$i - 1]}-07-01",
                    'pangkat_golongan'   => $i <= 3 ? 'III/A' : ($i <= 6 ? 'II/C' : 'I/B'),
                ]);
            }

            // ── 3b. GtkContact ─────────────────────────────────────────────
            if (!GtkContact::where('user_id', $user->id)->exists()) {
                GtkContact::create([
                    'user_id'        => $user->id,
                    'no_hp'          => '08' . str_pad((string)($i * 111111111), 11, '1', STR_PAD_LEFT),
                    'no_whatsapp'    => '08' . str_pad((string)($i * 111111111), 11, '1', STR_PAD_LEFT),
                    'kontak_darurat' => '08' . str_pad((string)(($i + 10) * 111111111), 11, '1', STR_PAD_LEFT),
                ]);
            }

            // ── 4. GtkWorkUnit ──────────────────────────────────────────────
            if (!GtkWorkUnit::where('user_id', $user->id)->where('work_unit_id', $workUnit->id)->exists()) {
                GtkWorkUnit::create([
                    'user_id'      => $user->id,
                    'work_unit_id' => $workUnit->id,
                    'jabatan'      => 'Guru',
                    'is_primary'   => true,
                ]);
            }

            $this->command->info("  ✅ {$name} <{$email}> | satuan_kerja: SD Putra | work_unit: {$workUnit->name}");
            $created++;
        }

        $this->command->info("\n✅ GtkDummySeeder selesai — {$created} guru dibuat.");
    }
}
