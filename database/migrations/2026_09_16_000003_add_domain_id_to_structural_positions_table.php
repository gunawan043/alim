<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structural_positions', function (Blueprint $table) {
            $table->foreignUuid('domain_id')
                ->nullable()
                ->after('role_id')
                ->constrained('domains')
                ->nullOnDelete();
            $table->index('domain_id');
        });

        // Backfill domain_id from role_id using the 14 official domain names.
        // JenisGtkSeeder writes role_id using these exact names:
        $roleToDomainCode = [
            'Super Admin' => 'super_admin',
            'Pimpinan' => 'pimpinan',
            'Satuan Pendidikan' => 'satuan_pendidikan',
            'Asrama' => 'asrama',
            'UKS' => 'uks',
            'Departemen Tahfidz' => 'departemen_tahfidz',
            'Departemen Bahasa' => 'departemen_bahasa',
            'Perpustakaan' => 'perpustakaan',
            'Satuan Keamanan' => 'satuan_keamanan',
            'Humas Personalia' => 'humas_personalia',
            'Unit Rumah Tangga' => 'unit_rumah_tangga',
            'Keuangan' => 'keuangan',
            'Teknologi Informasi' => 'teknologi_informasi',
            'Unit Pelayanan Gizi' => 'unit_pelayanan_gizi',
        ];

        $domainIds = [];
        foreach ($roleToDomainCode as $roleName => $domainCode) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            $domainId = DB::table('domains')
                ->where('code', $domainCode)
                ->value('id');

            if ($roleId && $domainId) {
                DB::table('structural_positions')
                    ->where('role_id', $roleId)
                    ->update(['domain_id' => $domainId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('structural_positions', function (Blueprint $table) {
            $table->dropIndex('domain_id');
            $table->dropForeign(['domain_id']);
            $table->dropColumn('domain_id');
        });
    }
};
