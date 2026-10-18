<?php

namespace App\Console\Commands;

use App\Models\TahfidzSetoran;
use App\Services\TahfidzProgressService;
use Illuminate\Console\Command;

class AutoVerifyTahfidzSetorans extends Command
{
    protected $signature = 'tahfidz:auto-verify-pending
                            {--school= : Batasi ke ID sekolah tertentu}
                            {--dry-run : Tampilkan jumlah tanpa mengubah data}';

    protected $description = 'Verifikasi otomatis seluruh setoran pending lama dan hitung ulang progres';

    public function handle(TahfidzProgressService $progress): int
    {
        $query = TahfidzSetoran::query()
            ->where('verification_status', 'pending')
            ->when($this->option('school'), fn ($q) => $q->whereHas('group', fn ($group) => $group->where('school_id', $this->option('school'))));

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Tidak ada setoran pending.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info($total.' setoran pending akan diverifikasi otomatis.');

            return self::SUCCESS;
        }

        $progressKeys = [];
        $verified = 0;

        $query->with('group:id,academic_year_id')->chunkById(200, function ($setorans) use (&$progressKeys, &$verified) {
            foreach ($setorans as $setoran) {
                $setoran->forceFill([
                    'verification_status' => 'verified',
                    'verified_by' => $setoran->teacher_id,
                    'verified_at' => now(),
                    'verification_notes' => 'auto-verified',
                ])->save();

                $progressKeys[$setoran->student_id.'|'.$setoran->academic_year_id.'|'.$setoran->tahfidz_group_id] = [
                    $setoran->student_id,
                    $setoran->academic_year_id,
                    $setoran->tahfidz_group_id,
                ];
                $verified++;
            }
        });

        foreach ($progressKeys as [$studentId, $academicYearId, $groupId]) {
            $progress->recalculateStudent($studentId, $academicYearId, $groupId);
        }

        $this->info($verified.' setoran diverifikasi otomatis dan progres dihitung ulang.');

        return self::SUCCESS;
    }
}
