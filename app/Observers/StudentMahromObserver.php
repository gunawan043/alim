<?php

namespace App\Observers;

use App\Models\StudentMahrom;
use App\Services\StudentLookupService;

/**
 * Menjaga data mahrom tetap sinkron di seluruh modul (termasuk asrama):
 * setiap perubahan mahrom menghapus cache profil santri pada lookup service.
 */
class StudentMahromObserver
{
    public function __construct(private readonly StudentLookupService $lookup) {}

    public function saved(StudentMahrom $mahrom): void
    {
        $this->lookup->invalidateCache($mahrom->student_id);
    }

    public function deleted(StudentMahrom $mahrom): void
    {
        $this->lookup->invalidateCache($mahrom->student_id);
    }
}
