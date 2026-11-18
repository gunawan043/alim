<?php

namespace App\Observers;

use App\Models\GradeLevelSubject;
use App\Services\Academic\RombelSubjectProvisioner;

/**
 * Ketika mapel tingkat ditambah/diubah, seluruh rombel aktif pada tingkat
 * tersebut langsung disusulkan struktur mapel dan buku nilainya.
 */
class GradeLevelSubjectObserver
{
    public function __construct(private readonly RombelSubjectProvisioner $provisioner)
    {
    }

    public function created(GradeLevelSubject $row): void
    {
        $this->provisioner->provisionGradeLevelRow($row);
    }

    public function updated(GradeLevelSubject $row): void
    {
        if (! $row->wasChanged(['subject_id', 'allocation_hours', 'academic_year_id', 'is_active'])) {
            return;
        }

        $this->provisioner->provisionGradeLevelRow($row);
    }
}
