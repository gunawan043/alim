<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Menyederhanakan jabatan struktural Asrama menjadi lima posisi:
 *
 *   1. Kepala Asrama
 *   2. Wakil Kepala Asrama
 *   3. Staf Asrama
 *   4. Staf Perizinan
 *   5. Tata Usaha Asrama
 *
 * Posisi lama yang kembar (Musrif/Musyrif, Musrifah/Musyrifah, TU Asrama,
 * Staf TU Asrama, Perizinan) dihapus setelah seluruh referensinya
 * dialihkan ke "Staf Asrama".
 */
return new class extends Migration
{
    private const ROLE_NAME = 'Asrama';

    private const STAF_ASRAMA_CODE = 'tenaga_pengasuhan_keasramaan_staf_asrama';

    /**
     * Posisi yang dipertahankan (selain Staf Asrama yang baru dibuat).
     *
     * @var list<string>
     */
    private const KEEP_CODES = [
        'tenaga_pengasuhan_keasramaan_kepala_asrama',
        'tenaga_pengasuhan_keasramaan_wakil_kepala_asrama',
        'tenaga_pengasuhan_keasramaan_staf_perizinan',
        'tenaga_pengasuhan_keasramaan_tata_usaha_asrama',
    ];

    /**
     * Posisi lama beserta id aslinya, dipakai untuk rollback.
     *
     * @var array<string, array{id: string, name: string, urutan: int}>
     */
    private const REMOVED_POSITIONS = [
        'tenaga_pengasuhan_keasramaan_musrif' => ['id' => '01a17efc-842f-71cd-a660-3ceec343b61d', 'name' => 'Musrif', 'urutan' => 3],
        'tenaga_pengasuhan_keasramaan_musrifah' => ['id' => '01a17efc-8430-7200-866f-42234a0d140f', 'name' => 'Musrifah', 'urutan' => 4],
        'tenaga_pengasuhan_keasramaan_musyrif' => ['id' => '01a17efc-8430-7200-866f-42234a3ad802', 'name' => 'Musyrif', 'urutan' => 5],
        'tenaga_pengasuhan_keasramaan_musyrifah' => ['id' => '01a17efc-8430-7200-866f-42234aa3aef4', 'name' => 'Musyrifah', 'urutan' => 6],
        'tenaga_pengasuhan_keasramaan_tu_asrama' => ['id' => '01a17efc-8431-706d-aaa6-dee51aaa480d', 'name' => 'TU Asrama', 'urutan' => 8],
        'tenaga_pengasuhan_keasramaan_staf_tu_asrama' => ['id' => '01a17efc-8431-706d-aaa6-dee51b4f4fa6', 'name' => 'Staf TU Asrama', 'urutan' => 9],
        'tenaga_pengasuhan_keasramaan_perizinan' => ['id' => '01a17efc-8432-72b5-9cf5-d7489c2895d0', 'name' => 'Perizinan', 'urutan' => 11],
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE_NAME)->value('id');
        $jenisId = DB::table('jenis_gtk')->where('nama', 'like', '%Pengasuhan%')->value('id');
        $domainId = DB::table('domains')->where('code', 'asrama')->value('id');

        if (! $roleId || ! $jenisId) {
            return;
        }

        $existing = DB::table('structural_positions')->where('code', self::STAF_ASRAMA_CODE)->first();

        DB::table('structural_positions')->updateOrInsert(
            ['code' => self::STAF_ASRAMA_CODE],
            [
                'id' => $existing->id ?? (string) Str::uuid(),
                'name' => 'Staf Asrama',
                'level' => 'pondok',
                'hierarchy_level' => 5,
                'jenis_gtk_id' => $jenisId,
                'kategori' => 'Jabatan',
                'description' => 'Staf pengasuhan asrama (generik untuk seluruh unit asrama).',
                'role_id' => $roleId,
                'domain_id' => $domainId,
                'urutan' => 3,
                'is_active' => 1,
                'updated_at' => now(),
                'created_at' => $existing->created_at ?? now(),
            ]
        );

        $stafId = DB::table('structural_positions')->where('code', self::STAF_ASRAMA_CODE)->value('id');

        if (! $stafId) {
            return;
        }

        $removeIds = DB::table('structural_positions')
            ->whereIn('code', array_keys(self::REMOVED_POSITIONS))
            ->pluck('id')
            ->all();

        if ($removeIds === []) {
            return;
        }

        // Alihkan seluruh referensi sebelum baris master dihapus, supaya
        // FK ON DELETE SET NULL tidak mengosongkan jabatan orang.
        foreach ([
            ['gtk_employments', 'jabatan_id'],
            ['gtk_work_unit', 'position_id'],
            ['dormitory_staff_assignments', 'position_id'],
            ['gtk_position_proposals', 'proposed_position_id'],
            ['structural_assignments', 'position_id'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->whereIn($column, $removeIds)->update([$column => $stafId]);
            }
        }

        DB::table('structural_positions')->whereIn('id', $removeIds)->delete();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE_NAME)->value('id');
        $jenisId = DB::table('jenis_gtk')->where('nama', 'like', '%Pengasuhan%')->value('id');
        $domainId = DB::table('domains')->where('code', 'asrama')->value('id');

        if (! $roleId || ! $jenisId) {
            return;
        }

        foreach (self::REMOVED_POSITIONS as $code => $position) {
            if (DB::table('structural_positions')->where('code', $code)->exists()) {
                continue;
            }

            DB::table('structural_positions')->insert([
                'id' => $position['id'],
                'code' => $code,
                'name' => $position['name'],
                'level' => 'pondok',
                'hierarchy_level' => 5,
                'jenis_gtk_id' => $jenisId,
                'kategori' => 'Jabatan',
                'role_id' => $roleId,
                'domain_id' => $domainId,
                'urutan' => $position['urutan'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('structural_positions')->where('code', self::STAF_ASRAMA_CODE)->delete();
    }
};
