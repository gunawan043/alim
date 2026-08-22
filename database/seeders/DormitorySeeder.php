<?php

namespace Database\Seeders;

use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DormitorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Buat role Asrama jika belum ada ───────────────────────────
        $asramaRoleId = DB::table('roles')->where('name', 'Asrama')->where('guard_name', 'web')->value('id');
        if (! $asramaRoleId) {
            $role = \Spatie\Permission\Models\Role::create([
                'name' => 'Asrama',
                'guard_name' => 'web',
                'level' => 17,
                'description' => 'Asrama (Kepala, Admin, Wali — divisi berdasarkan jabatan)',
            ]);
            $asramaRoleId = $role->id;
        }

        // ── User Kepala Asrama ─────────────────────────────────────────
        $kepalaAsrama = User::firstOrCreate(
            ['email' => 'kepala.asrama@example.com'],
            [
                'name' => 'Ustadz Fulan',
                'password' => 'password123',
                'is_active' => true,
            ]
        );
        DB::table('model_has_roles')->updateOrInsert(
            ['model_type' => 'App\Models\User', 'model_id' => $kepalaAsrama->id, 'role_id' => $asramaRoleId],
            ['role_id' => $asramaRoleId]
        );
        $this->command->info("  ✅ Kepala Asrama: {$kepalaAsrama->name} <{$kepalaAsrama->email}> → Asrama");

        // ── User Admin Asrama ──────────────────────────────────────────
        $adminAsrama = User::firstOrCreate(
            ['email' => 'admin.asrama@example.com'],
            [
                'name' => 'Ustadz Fulan',
                'password' => 'password123',
                'is_active' => true,
            ]
        );
        DB::table('model_has_roles')->updateOrInsert(
            ['model_type' => 'App\Models\User', 'model_id' => $adminAsrama->id, 'role_id' => $asramaRoleId],
            ['role_id' => $asramaRoleId]
        );
        $this->command->info("  ✅ Admin Asrama: {$adminAsrama->name} <{$adminAsrama->email}> → Asrama");

        // ── Ambil work_unit & school default ───────────────────────────
        $workUnit = \App\Models\WorkUnit::first();
        $school = \App\Models\School::first();
        if (! $workUnit) {
            $this->command->warn('⚠️ WorkUnit tidak ditemukan. Lewati seeder asrama.');

            return;
        }

        // ── Asrama Utama ────────────────────────────────────────────────
        $asrama = Dormitory::firstOrCreate(
            ['code' => 'ASR-001'],
            [
                'work_unit_id' => $workUnit->id,
                'school_id' => $school?->id,
                'name' => 'Asrama Pusat Putra',
                'gender' => 'putra',
                'address' => 'Jl. Pondok, Mataram NTB',
                'phone' => '0878-1234-5678',
                'capacity' => 80,
                'total_rooms' => 20,
                'total_wings' => 2,
                'head_id' => $kepalaAsrama->id,
                'is_active' => true,
            ]
        );
        $this->command->info("  ✅ Asrama: {$asrama->name} (ID: {$asrama->id})");

        // ── Wing / Blok ────────────────────────────────────────────────
        $wings = [
            ['code' => 'A', 'name' => 'Blok A — Lantai 1', 'floor' => 1, 'gender' => 'putra', 'capacity' => 40, 'total_rooms' => 10],
            ['code' => 'B', 'name' => 'Blok B — Lantai 2', 'floor' => 2, 'gender' => 'putra', 'capacity' => 40, 'total_rooms' => 10],
        ];
        foreach ($wings as $wingData) {
            $wing = DormitoryWing::firstOrCreate(
                ['dormitory_id' => $asrama->id, 'code' => $wingData['code']],
                array_merge($wingData, ['dormitory_id' => $asrama->id, 'is_active' => true])
            );
            $this->command->info("    ✅ Wing: {$wing->name} (ID: {$wing->id})");

            // ── Kamar per Wing ────────────────────────────────────────
            for ($i = 1; $i <= $wingData['total_rooms']; $i++) {
                $roomCode = sprintf('%s-%02d', $wingData['code'], $i);
                DormitoryRoom::firstOrCreate(
                    ['dormitory_id' => $asrama->id, 'code' => $roomCode],
                    [
                        'wing_id' => $wing->id,
                        'name' => "Kamar {$roomCode}",
                        'floor' => $wingData['floor'],
                        'gender' => 'putra',
                        'capacity' => 4,
                        'room_type' => 'reguler',
                        'is_active' => true,
                    ]
                );
            }
            $this->command->info("      ✅ {$wingData['total_rooms']} kamar dibuat untuk {$wing->name}");
        }

        // ── Asrama Putri ────────────────────────────────────────────────
        $asramaPutri = Dormitory::firstOrCreate(
            ['code' => 'ASR-002'],
            [
                'work_unit_id' => $workUnit->id,
                'school_id' => $school?->id,
                'name' => 'Asrama Putri',
                'gender' => 'putri',
                'address' => 'Jl. Pondok, Mataram NTB',
                'phone' => '0878-9876-5432',
                'capacity' => 60,
                'total_rooms' => 15,
                'total_wings' => 2,
                'head_id' => null,
                'is_active' => true,
            ]
        );
        $this->command->info("  ✅ Asrama Putri: {$asramaPutri->name} (ID: {$asramaPutri->id})");

        $putriWings = [
            ['code' => 'C', 'name' => 'Blok C — Utama', 'floor' => 1, 'gender' => 'putri', 'capacity' => 30, 'total_rooms' => 8],
            ['code' => 'D', 'name' => 'Blok D — Lantai 2', 'floor' => 2, 'gender' => 'putri', 'capacity' => 30, 'total_rooms' => 7],
        ];
        foreach ($putriWings as $pw) {
            $pwing = DormitoryWing::firstOrCreate(
                ['dormitory_id' => $asramaPutri->id, 'code' => $pw['code']],
                array_merge($pw, ['dormitory_id' => $asramaPutri->id, 'is_active' => true])
            );
            for ($i = 1; $i <= $pw['total_rooms']; $i++) {
                $roomCode = sprintf('%s-%02d', $pw['code'], $i);
                DormitoryRoom::firstOrCreate(
                    ['dormitory_id' => $asramaPutri->id, 'code' => $roomCode],
                    [
                        'wing_id' => $pwing->id,
                        'name' => "Kamar {$roomCode}",
                        'floor' => $pw['floor'],
                        'gender' => 'putri',
                        'capacity' => 4,
                        'room_type' => 'reguler',
                        'is_active' => true,
                    ]
                );
            }
            $this->command->info("    ✅ {$pw['total_rooms']} kamar dibuat untuk {$pwing->name}");
        }

        $this->command->info('✅ Dormitory seeder completed!');
    }
}
