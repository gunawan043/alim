<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aturan aktivasi / penonaktifan SK.
 *
 * SK Pembagian Tugas adalah SATU dokumen majemuk: isinya dibaca ulang lalu
 * DITIMPA oleh sync matriks saat SK lain disimpan. Karena itu, pada satu
 * sekolah + satu tahun ajaran hanya boleh ada satu SK Pembagian Tugas aktif;
 * mengaktifkan SK lain menonaktifkan SK yang sedang aktif.
 *
 * SK jenis lain (mis. SK Penugasan Pengawas) bisa diaktifkan tanpa saling
 * membatalkan, karena beberapa SK berjenis sama boleh berlaku bersamaan.
 */
class InstitutionDecreeService
{
    /**
     * SK satu jenis pada sekolah + tahun ajaran yang sedang aktif.
     *
     * @return Collection<int, InstitutionDecree>
     */
    public function activePeers(InstitutionDecree $decree)
    {
        return InstitutionDecree::query()
            ->where('school_id', $decree->school_id)
            ->where('academic_year_id', $decree->academic_year_id)
            ->where('decree_type', $decree->decree_type)
            ->where('status', InstitutionDecree::STATUS_ACTIVE)
            ->whereKeyNot($decree->id)
            ->orderByDesc('issued_date')
            ->get();
    }

    /**
     * Jenis SK yang tidak boleh berlaku ganda: mengaktifkan satu menonaktifkan
     * yang lain. Ditentukan dari isi matriks, bukan hanya nama jenis.
     */
    public function replacesSameType(InstitutionDecree $decree): bool
    {
        return $decree->decree_type === 'SK Pembagian Tugas'
            || $decree->teachingAssignments()->exists()
            || OtherTeacherTask::where('decree_id', $decree->id)->exists();
    }

    /**
     * Aktifkan SK, lalu nonaktifkan SK sajenis yang masih aktif.
     *
     * @return array{replaced: array<int, InstitutionDecree>}
     */
    public function activate(InstitutionDecree $decree, ?User $actor = null): array
    {
        $from = $decree->status;
        $replaced = $this->replacesSameType($decree) ? $this->activePeers($decree) : collect();

        DB::transaction(function () use ($decree, $replaced) {
            foreach ($replaced as $peer) {
                $peer->forceFill(['status' => InstitutionDecree::STATUS_ARCHIVED])->save();
            }

            $decree->forceFill(['status' => InstitutionDecree::STATUS_ACTIVE])->save();
        });

        $this->audit('INSTITUTION_DECREE_ACTIVATED', $decree, $actor, [
            'from' => $from,
            'replaced' => $replaced->map(fn ($peer) => [
                'id' => $peer->id,
                'decree_number' => $peer->decree_number,
            ])->values()->all(),
        ]);

        foreach ($replaced as $peer) {
            $this->audit('INSTITUTION_DECREE_DEACTIVATED', $peer, $actor, [
                'from' => InstitutionDecree::STATUS_ACTIVE,
                'replaced_by' => [
                    'id' => $decree->id,
                    'decree_number' => $decree->decree_number,
                ],
            ]);
        }

        return ['replaced' => $replaced->values()->all()];
    }

    /**
     * Nonaktifkan SK dengan memindahkannya ke Arsip — data tidak dihapus,
     * sehingga bisa diaktifkan kembali.
     */
    public function deactivate(InstitutionDecree $decree, ?User $actor = null): void
    {
        $from = $decree->status;

        $decree->forceFill(['status' => InstitutionDecree::STATUS_ARCHIVED])->save();

        $this->audit('INSTITUTION_DECREE_DEACTIVATED', $decree, $actor, ['from' => $from]);
    }

    private function audit(string $action, InstitutionDecree $decree, ?User $actor, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'table_name' => 'institution_decrees',
            'record_id' => $decree->id,
            'record_type' => InstitutionDecree::class,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
            'metadata' => array_merge([
                'decree_number' => $decree->decree_number,
                'decree_type' => $decree->decree_type,
                'school_id' => $decree->school_id,
                'academic_year_id' => $decree->academic_year_id,
            ], $metadata),
        ]);
    }
}
