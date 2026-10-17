<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_units', function (Blueprint $table) {
            $table->foreignUuid('domain_id')
                ->nullable()
                ->after('type')
                ->constrained('domains')
                ->nullOnDelete();

            $table->index('domain_id');
        });

        // Backfill domain_id based on work unit type and code
        $mappings = [
            'Unit Akademik' => 'satuan_pendidikan',
            'DEP-KEU' => 'keuangan',
            'DEP-URT' => 'unit_rumah_tangga',
            'DEP-UKS' => 'uks',
            'DEP-SATPAM' => 'satuan_keamanan',
            'DEP-PERPUS' => 'perpustakaan',
            'DEP-TIJ' => 'teknologi_informasi',
            'DEP-HUMAS' => 'humas_personalia',
            'DEP-UPGIZI' => 'unit_pelayanan_gizi',
            'DEP-TAH' => 'departemen_tahfidz',
            'DEP-BHS' => 'departemen_bahasa',
            'DEP-LAB' => 'teknologi_informasi',
        ];

        foreach ($mappings as $codeOrType => $domainCode) {
            $domainId = DB::table('domains')->where('code', $domainCode)->value('id');
            if (! $domainId) {
                continue;
            }

            // Match by type (e.g., 'Unit Akademik') or by code (e.g., 'DEP-KEU')
            DB::table('work_units')
                ->where(function ($q) use ($codeOrType) {
                    $q->where('type', $codeOrType)
                        ->orWhere('code', $codeOrType);
                })
                ->whereNull('domain_id')
                ->update(['domain_id' => $domainId]);
        }

        // Asrama units get domain from parent or default to asrama
        $asramaDomainId = DB::table('domains')->where('code', 'asrama')->value('id');
        if ($asramaDomainId) {
            // Get parent domain for asrama work units
            $asramaWUs = DB::table('work_units')
                ->where('type', 'Unit Penunjang Akademik')
                ->where('parent_id', '!=', null)
                ->get(['id', 'parent_id']);

            foreach ($asramaWUs as $wu) {
                $parentDomain = DB::table('work_units')
                    ->join('domains', 'work_units.domain_id', '=', 'domains.id')
                    ->where('work_units.id', $wu->parent_id)
                    ->value('domains.id');

                DB::table('work_units')
                    ->where('id', $wu->id)
                    ->whereNull('domain_id')
                    ->update(['domain_id' => $parentDomain ?? $asramaDomainId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_units', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->dropIndex(['domain_id']);
            $table->dropColumn('domain_id');
        });
    }
};
