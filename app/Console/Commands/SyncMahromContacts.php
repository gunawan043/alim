<?php

namespace App\Console\Commands;

use App\Models\StudentMahrom;
use Illuminate\Console\Command;

/**
 * Sinkronkan nomor HP mahrom yang masih kosong.
 *
 * Sumber: nomor HP/telepon santri yang terdaftar (umumnya nomor orang tua/wali).
 * Hanya mahrom utama (atau mahrom tunggal) yang diisi agar tidak semua peran
 * memakai nomor yang sama. Data yang sudah terisi TIDAK ditimpa.
 */
class SyncMahromContacts extends Command
{
    protected $signature = 'dormitory:sync-mahrom-contacts
                            {--dry-run : Tampilkan jumlah tanpa mengubah data}';

    protected $description = 'Isi nomor HP mahrom kosong dari nomor santri/wali terdaftar';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $mahroms = StudentMahrom::query()
            ->with('student:id,name,mobile_phone,phone')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('phone')->orWhere('phone', ''))
            ->get();

        $candidates = 0;
        $updated = 0;
        $withoutSource = 0;

        // Jumlah mahrom aktif per santri untuk menentukan "mahrom tunggal".
        $counts = StudentMahrom::query()
            ->selectRaw('student_id, count(*) as total')
            ->where('is_active', true)
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        foreach ($mahroms as $mahrom) {
            $phone = $mahrom->student?->mobile_phone ?: $mahrom->student?->phone;
            $phone = $this->normalizePhone($phone);

            if (! $phone) {
                $withoutSource++;

                continue;
            }

            $isSingle = ($counts[$mahrom->student_id] ?? 0) <= 1;

            if (! $mahrom->is_primary && ! $isSingle) {
                continue;
            }

            $candidates++;

            if ($dryRun) {
                continue;
            }

            $mahrom->forceFill(['phone' => $phone])->save();
            $updated++;
        }

        $this->table(['Keterangan', 'Jumlah'], [
            ['Mahrom tanpa nomor HP', $mahroms->count()],
            ['Kandidat diisi (utama/tunggal)', $candidates],
            ['Tanpa sumber nomor', $withoutSource],
            ['Diperbarui', $dryRun ? 0 : $updated],
        ]);

        if ($dryRun) {
            $this->warn('Mode dry-run: tidak ada data yang diubah.');
        }

        return self::SUCCESS;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9+]/', '', $phone);

        return $digits !== '' ? mb_substr($digits, 0, 20) : null;
    }
}
