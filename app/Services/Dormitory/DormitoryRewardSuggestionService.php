<?php

declare(strict_types=1);

namespace App\Services\Dormitory;

use App\Models\DormitoryPointCatalog;
use App\Models\StudentAchievement;

class DormitoryRewardSuggestionService
{
    /**
     * Usulan katalog penghargaan asrama berdasarkan tingkat dan peringkat prestasi.
     * Katalog tetap dapat diubah oleh Kepala/Wakil Asrama saat approval.
     */
    public function suggest(StudentAchievement $achievement): ?DormitoryPointCatalog
    {
        $code = $this->catalogCode($achievement);

        if (! $code) {
            return null;
        }

        return DormitoryPointCatalog::query()
            ->active()
            ->kind('reward')
            ->where('code', $code)
            ->first();
    }

    public function catalogCode(StudentAchievement $achievement): ?string
    {
        if ($achievement->achievement_type === 'hafalan') {
            return $achievement->hafalan_category === 'hadits' ? 'R-21' : 'R-20';
        }

        $winner = in_array($achievement->position, [
            'juara_1', 'juara_2', 'juara_3',
            'harapan_1', 'harapan_2', 'harapan_3',
            'mumtaz_murtafi',
        ], true);

        if ($achievement->level === 'internal') {
            return $winner ? 'R-03' : null;
        }

        $winnerCodes = [
            'kecamatan' => 'R-12',
            'kabupaten_kota' => 'R-13',
            'provinsi' => 'R-14',
            'nasional' => 'R-15',
            'internasional' => 'R-16',
        ];

        $participantCodes = [
            'kecamatan' => 'R-07',
            'kabupaten_kota' => 'R-08',
            'provinsi' => 'R-09',
            'nasional' => 'R-10',
            'internasional' => 'R-11',
        ];

        return $winner
            ? ($winnerCodes[$achievement->level] ?? null)
            : ($participantCodes[$achievement->level] ?? null);
    }
}
