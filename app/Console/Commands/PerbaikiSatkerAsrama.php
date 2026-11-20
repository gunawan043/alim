<?php

namespace App\Console\Commands;

use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan satuan kerja dan jabatan GTK pengasuhan/keamanan dengan
 * SK Pembagian Tugas: memindahkan staf putra yang salah masuk unit putri,
 * menghapus baris satker ganda, dan menyamakan jabatan ke master
 * structural_positions.
 *
 * Default-nya dry-run. Tambahkan --commit untuk menulis.
 */
class PerbaikiSatkerAsrama extends Command
{
    protected $signature = 'asrama:perbaiki-satker
                            {--commit : Tulis perubahan ke database}';

    protected $description = 'Perbaiki satuan kerja & jabatan GTK asrama sesuai SK (dry-run default)';

    private const UNIT = [
        'smp_putri' => '39d25c6f-fd1a-4d16-9dc1-8b9f2ca2e2b9',
        'sma_putri' => '662460a4-2eed-490b-9e32-eca6f2d9064c',
        'ma_plus' => 'ae48bbd7-dfd6-48c3-8d53-728f42ae8ca8',
        'smp_putra' => 'bdacb862-6236-4435-807f-8389594f6407',
        'pps' => 'b1922aea-0433-4158-aa96-7032ef6bccb5',
        'keamanan' => '410f55f9-1685-4a03-af88-6902dfc1f619',
    ];

    private const POSITION = [
        'kepala' => 'tenaga_pengasuhan_keasramaan_kepala_asrama',
        'wakil' => 'tenaga_pengasuhan_keasramaan_wakil_kepala_asrama',
        'staf' => 'tenaga_pengasuhan_keasramaan_staf_asrama',
        'perizinan' => 'tenaga_pengasuhan_keasramaan_staf_perizinan',
        'tu' => 'tenaga_pengasuhan_keasramaan_tata_usaha_asrama',
    ];

    private const JENIS_PENGASUHAN = 'dc268a18-ae2b-4dcb-8f32-c796eb9316b9';

    /**
     * Unit di luar target yang tetap perlu ikut dicari, karena stafnya akan
     * dipindahkan (mis. staf asrama diniyah yang masih tercatat di unit
     * akademik PPS Diniyah).
     *
     * @var list<string>
     */
    private const INDEX_EXTRA_UNITS = [
        '69729627-5638-407b-88f4-cce6d3b6dd22',
    ];

    private const JENIS_KEAMANAN = '8b797fa5-c1a7-410c-bd82-687a55e65bf6';

    private const ROLE_KEAMANAN = 'Satuan Keamanan';

    /**
     * @return array<string, array{0: string, 1: string|null, 2?: string}>
     */
    private function targets(): array
    {
        return [
            // ── Asrama SMP Putri ────────────────────────────────────────
            'ZAKARIA' => ['smp_putri', 'kepala'],
            'FITRI MAULIDA' => ['smp_putri', 'wakil'],
            'Yulia Restiyana' => ['smp_putri', 'staf'],
            'LUQYANA RAHMA' => ['smp_putri', 'staf'],
            'Aolia Fitri' => ['smp_putri', 'staf'],
            'Zahwa Bagis' => ['smp_putri', 'staf'],
            'Istiqomah' => ['smp_putri', 'staf'],
            'Baiq Siti Patmah' => ['smp_putri', 'staf'],
            'Mimma Humaira Az Zahra’' => ['smp_putri', 'staf'],

            // ── Asrama SMA Putri ────────────────────────────────────────
            'SAPARUDDIN' => ['sma_putri', 'kepala'],
            'HAERATUNNISA' => ['sma_putri', 'wakil'],
            'Zawiyatillaili' => ['sma_putri', 'staf'],
            'KHAERANI SOLIHAH' => ['sma_putri', 'staf'],
            'NURUL HASANA' => ['sma_putri', 'staf'],
            'ULFA UNISARA' => ['sma_putri', 'staf'],
            'Nuryani' => ['sma_putri', 'staf'],
            'Anisa Aulia Putri' => ['sma_putri', 'staf'],
            'AZMI ALLAI RINDIANI' => ['sma_putri', 'staf'],
            'NABILA' => ['sma_putri', 'staf'],
            'Zatil Hidayah' => ['sma_putri', 'staf'],

            // ── Asrama MA Plus (putra) ──────────────────────────────────
            'SAEFUDDIN JAZA' => ['ma_plus', 'kepala'],
            'MUHAMMAD FAYSAL' => ['ma_plus', 'staf'],
            'ANANDA PATRURENDRA' => ['ma_plus', 'staf'],
            'REDHO APREZA PUTRA' => ['ma_plus', 'staf'],
            'UMAR RAHMAT ALAM RAMADHAN' => ['ma_plus', 'staf'],
            'IMAM SAPII' => ['ma_plus', 'staf'],
            'H. MUHAZZAM' => ['ma_plus', 'staf'],
            'Mahardika Putra Emas' => ['ma_plus', 'staf'],

            // ── Asrama SMP Putra ────────────────────────────────────────
            'JAMALUDIN' => ['smp_putra', 'kepala'],
            'FATHUL ULUM' => ['smp_putra', 'staf'],
            'EZRA AMARIL' => ['smp_putra', 'staf'],
            'DANIAL' => ['smp_putra', 'staf'],
            'RIAN RAHMANDI' => ['smp_putra', 'staf'],
            'CHAIRUL AMIN' => ['smp_putra', 'staf'],
            'HENDRA HERMAYADI' => ['smp_putra', 'staf'],
            'FITRAH WAHYU MAHARAJA' => ['smp_putra', 'staf'],
            'DIDIK INDRAJATI HAKIKI' => ['smp_putra', 'staf'],
            'MUHAMMAD ALFIN HIDAYAT' => ['smp_putra', 'staf'],
            'HENDRI EFENDI' => ['smp_putra', 'staf'],
            'UTUH ARYA GUNA' => ['smp_putra', 'staf'],

            // Perizinan putra (bukan putri)
            'ZAINUDDIN' => ['smp_putra', 'perizinan'],
            'M. THORIQ ABDUL AZIZ' => ['smp_putra', 'perizinan'],
            'Ibnu Kaldun' => ['smp_putra', 'perizinan'],

            // ── Asrama PPS Diniyah ──────────────────────────────────────
            'ASSHONANY FAJRIN' => ['pps', 'staf', 'pengasuhan'],
            'LALU ZANZABIL' => ['pps', 'staf', 'pengasuhan'],
            'IMAN RAHMADI' => ['pps', 'staf', 'pengasuhan'],
            'JUMAWARDIN' => ['pps', 'staf', 'pengasuhan'],


            // ── Satuan Keamanan ─────────────────────────────────────────
            'FIKY SETIAWAN' => ['keamanan', null],
            'YANTO' => ['keamanan', null],
            'BURHANUDIN' => ['keamanan', null],
            'AGUS PUTRA JAYADI' => ['keamanan', null],
            'RAMELI AHMAD' => ['keamanan', null],
            'H. SARI SUKTI' => ['keamanan', null],
            'BUDIMAN' => ['keamanan', null],
            'MUHAMMAD MUNIR' => ['keamanan', null],
            'PUADI' => ['keamanan', null],
            'MUZANNI' => ['keamanan', null],
            'MUHAMAD SETIAGAS NEGARA' => ['keamanan', null],
            'Muhammad Rivaldi Ikbal' => ['keamanan', null],
            'IMRON ROSADI' => ['keamanan', null],
            'AKHLAN MAULIDI' => ['keamanan', null],
            'Ahmad Hasanudin' => ['keamanan', null],
            'HERI SUTOMO' => ['keamanan', null],
            'KUSMAYADI' => ['keamanan', null],
            'Khozin Sumadani' => ['keamanan', null],
            'ZOHRIADI' => ['keamanan', null],
            'I WAYAN GEDE KOESWARDANA' => ['keamanan', null],
            'MUHAMMAD NASIR' => ['keamanan', null],
            'AGAM SASTRA HAMZANI' => ['keamanan', null],
        ];
    }

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');

        $positions = StructuralPosition::whereIn('code', array_values(self::POSITION))
            ->get()
            ->keyBy('code');

        $missingPosition = array_diff(array_values(self::POSITION), $positions->keys()->all());
        if ($missingPosition !== []) {
            $this->error('Master posisi belum lengkap: '.implode(', ', $missingPosition));
            $this->line('Jalankan `php artisan migrate --force` lebih dulu.');

            return self::FAILURE;
        }

        $unitNames = WorkUnit::whereIn('id', array_values(self::UNIT))->pluck('name', 'id');

        $index = $this->userIndex();
        $rows = [];
        $missing = [];
        $duplicates = 0;
        $moved = 0;

        foreach ($this->targets() as $name => $target) {
            [$unitKey, $positionKey] = $target;
            $jenisKey = $target[2] ?? null;

            $user = $this->resolveUser($index, $name);

            if (! $user) {
                $missing[] = $name;

                continue;
            }

            $unitId = self::UNIT[$unitKey];
            $unitName = $unitNames[$unitId] ?? $unitId;
            $position = $positionKey ? $positions[self::POSITION[$positionKey]] : null;
            $jabatan = $position?->name ?? 'Petugas Keamanan';

            $current = GtkWorkUnit::where('user_id', $user->id)->get();
            $employment = GtkEmployment::where('user_id', $user->id)->first();

            $oldUnits = $current
                ->map(fn (GtkWorkUnit $row) => (WorkUnit::find($row->work_unit_id)?->name ?? $row->work_unit_id).($row->is_primary ? '' : ' (sekunder)'))
                ->implode(' + ');

            $extraRows = $current->where('work_unit_id', '!=', $unitId)->count();
            $duplicates += $extraRows;

            $isMove = ! $current->contains(fn (GtkWorkUnit $row) => $row->work_unit_id === $unitId)
                || $current->where('work_unit_id', $unitId)->where('is_primary', false)->isNotEmpty();

            if ($isMove) {
                $moved++;
            }

            $rows[] = [
                $name,
                $oldUnits ?: '-',
                $employment?->jabatan ?: '-',
                $unitName,
                $jabatan,
                $extraRows > 0 ? $extraRows.' baris' : '-',
            ];

            if ($commit) {
                $jenisId = match (true) {
                    $jenisKey === 'pengasuhan' => self::JENIS_PENGASUHAN,
                    $unitKey === 'keamanan' => self::JENIS_KEAMANAN,
                    default => null,
                };

                $this->apply($user, $unitId, $unitName, $jabatan, $position?->id, $unitKey, $employment, $jenisId);
            }
        }

        $this->table(
            ['Nama', 'Satker sekarang', 'Jabatan sekarang', 'Satker target', 'Jabatan target', 'Hapus'],
            $rows,
        );

        $this->newLine();
        $this->line('Total diproses : '.count($rows));
        $this->line('Pindah unit    : '.$moved);
        $this->line('Baris dihapus  : '.$duplicates);

        if ($missing !== []) {
            $this->newLine();
            $this->warn('Tidak ditemukan di database (dilewati): '.implode(', ', $missing));
        }

        if (! $commit) {
            $this->newLine();
            $this->info('DRY-RUN — tidak ada data yang ditulis. Jalankan ulang dengan --commit.');
        } else {
            $this->newLine();
            $this->info('Selesai. Perubahan sudah ditulis.');
        }

        return self::SUCCESS;
    }

    private function apply(
        User $user,
        string $unitId,
        string $unitName,
        string $jabatan,
        ?string $positionId,
        string $unitKey,
        ?GtkEmployment $employment,
        ?string $jenisId = null,
    ): void {
        DB::transaction(function () use ($user, $unitId, $unitName, $jabatan, $positionId, $unitKey, $employment, $jenisId) {
            // Hapus baris satker lain, lalu pastikan satu baris primary di unit target.
            GtkWorkUnit::where('user_id', $user->id)
                ->where('work_unit_id', '!=', $unitId)
                ->delete();

            GtkWorkUnit::updateOrCreate(
                ['user_id' => $user->id, 'work_unit_id' => $unitId],
                ['jabatan' => $jabatan, 'position_id' => $positionId, 'is_primary' => true],
            );

            GtkWorkUnit::where('user_id', $user->id)
                ->where('work_unit_id', '!=', $unitId)
                ->update(['is_primary' => false]);

            if ($employment) {
                $employment->jabatan = $jabatan;
                $employment->jabatan_id = $positionId;
                $employment->satuan_kerja = $unitName;

                if ($jenisId) {
                    $employment->jenis_gtk_id = $jenisId;
                }

                $employment->save();
            }

            if ($unitKey === 'keamanan' && ! $user->hasRole(self::ROLE_KEAMANAN)) {
                $user->syncRoles([self::ROLE_KEAMANAN]);
            }

            if ($unitKey === 'pps' && ! $user->hasRole('Asrama')) {
                $user->assignRole('Asrama');
            }
        });
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, User>>
     */
    private function userIndex(): array
    {
        $userIds = GtkWorkUnit::whereIn(
            'work_unit_id',
            array_merge(array_values(self::UNIT), self::INDEX_EXTRA_UNITS),
        )->pluck('user_id');

        return User::whereIn('id', $userIds)
            ->get()
            ->groupBy(fn (User $user) => $this->normalize($user->name))
            ->all();
    }

    /**
     * @param  array<string, \Illuminate\Support\Collection<int, User>>  $index
     */
    private function resolveUser(array $index, string $name): ?User
    {
        $matches = $index[$this->normalize($name)] ?? null;

        return $matches?->first();
    }

    private function normalize(string $value): string
    {
        $value = str_replace(['’', '`', '´'], "'", $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', strtolower($value)) ?? '';

        return trim($value);
    }
}
