<?php

namespace App\Console\Commands;

use App\Models\GtkAddress;
use App\Models\GtkContact;
use App\Models\GtkEducation;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\JenisGtk;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportGtkDataCommand extends Command
{
    protected $signature = 'gtk:import
        {file : Path absolut/relatif ke file TSV/CSV}
        {--delimiter= : Pemisah kolom (default: tab bila terdeteksi, selain itu ;)}
        {--dry-run : Hanya tampilkan ringkasan tanpa menulis ke database}
        {--update : Perbarui data GTK yang sudah ada (default: lewati)}';

    protected $description = 'Impor data GTK (users, profil, kepegawaian, satuan kerja, alamat, kontak, pendidikan) dari file TSV/CSV.';

    /** Alias satuan kerja (dinormalisasi) => kode work_units. */
    private array $satuanKerjaAliases = [
        'PERPUSTAKAAN' => 'DEP-PERPUS',
        'DEPARTEMEN TAHFIDZ' => 'DEP-TAH',
        'DEPARTEMEN BAHASA' => 'DEP-BHS',
        'HUMAS DAN PERSONALIA' => 'DEP-HUMAS',
        'HUMAS & PERSONALIA' => 'DEP-HUMAS',
        'KEUANGAN' => 'DEP-KEU',
        'KEAMANAN' => 'DEP-SATPAM',
        'UNIT KESEHATAN SEKOLAH (UKS)' => 'DEP-UKS',
        'TEKNOLOGI INFORMASI JARINGAN' => 'DEP-TIJ',
        'UNIT GIZI DAN LOGISTIK (UGL)' => 'DEP-UPGIZI',
        'UNIT RUMAH TANGGA (URT)' => 'DEP-URT',
        'SD IT PUTRA ABU HURAIRAH MATARAM' => 'UAK-001',
        'SD IT PUTRI ABU HURAIRAH MATARAM' => 'UAK-002',
        'SMP IT PUTRA ABU HURAIRAH MATARAM' => 'UAK-003',
        'SMP IT PUTRI ABU HURAIRAH MATARAM' => 'UAK-004',
        'SMP DAN SMA IT PUTRA ABU HURAIRAH' => 'UAK-005',
        'SMA IT PUTRI ABU HURAIRAH MATARAM' => 'UAK-006',
        'MA PLUS ABU HURAIRAH MATARAM' => 'UAK-007',
        'DINIYYAH ABU HURAIRAH MATARAM' => 'UAK-008',
        'PENGASUHAN PONDOK PUTRA' => 'PNG-001',
        'PENGASUHAN SMP IT PUTRI ABU HURAIRAH MATARAM' => 'PNG-002',
        'PENGASUHAN SMA IT PUTRI ABU HURAIRAH MATARAM' => 'PNG-003',
        'TOP MANAGEMENT' => 'PIM-001',
        'BIDANG DAKWAH DAN SOSIAL' => 'DEP-DAK',
        'PAUD ABU HURAIRAH MATARAM' => 'UAK-009',
        'PRODI PGMI' => 'UAK-010',
        'KESEKRETARIATAN' => 'SEC-001',
        'MANAGEMENT REFRESENTATIF' => 'DEP-HUMAS',
    ];

    /** Satuan kerja yang barisnya tidak diimpor. */
    private array $satuanKerjaSkip = [
        'BADAN USAHA MILIK PONDOK (BUMP)',
        'BADAN USAHAMILIK PONDOK (BUMP)',
    ];

    /** Alias jabatan (dinormalisasi) => nama structural_positions. */
    private array $jabatanAliases = [
        'KEPALA STAF TATA USAHA' => 'KEPALA TATA USAHA',
        'STAF STAF TATA USAHA PENGASUHAN PUTRA' => 'STAF TATA USAHA',
        'STAF HUMAS & PERSONALIA' => 'STAF HUMAS',
        'STAF PERSONALIA' => 'STAF PERSONALIA',
        'KEPALA UNIT GIZI & LOGISTIK (UGL)' => 'KEPALA UNIT GIZI & LOGISTIK',
        'STAF UNIT GIZI & LOGISTIK (UGL)' => 'STAF GIZI',
        'STAF UNIT RUMAH TANGGA (SUB KEBERSIHAN & PERTAMANAN)' => 'STAF URT',
        'STAF UNIT RUMAH TANGGA (SUB SARPRAS)' => 'STAF URT',
        'STAF UNIT RUMAH TANGGA (SUB TRANPORTASI)' => 'STAF URT',
        'STAF UNIT RUMAH TANGGA (URT)' => 'STAF URT',
        'STAF KEAMANAN' => 'PETUGAS KEAMANAN',
        'KEPALA UNIT RUMAH TANGGA (URT)' => 'KEPALA UNIT RUMAH TANGGA',
        'KOORDINATOR TEKNOLOGI INFORMASI & JARINGAN (TIJ)' => 'KEPALA UNIT TEKNOLOGI INFORMASI',
        'STAF TEKNOLOGI INFORMASI & JARINGAN (TIJ)' => 'IT SUPPORT',
        "MUDIR 'AMM PONPES ABU HURAIRAH" => 'MUDIR',
        'MUDIR PAH MATARAM' => 'MUDIR',
        'GURU PAUD' => 'GURU KELAS',
        'GURU BAHASA INGGRIS' => 'GURU UMUM',
        'KEPALA PENGASUHAN' => 'KEPALA ASRAMA',
        'WAKIL KEPALA PENGASUHAN' => 'WAKIL KEPALA ASRAMA',
        'STAF MANAGEMENT REFRESENTATIF' => 'STAF HUMAS',
        'STAF HUMAS & PERSONALIA (SUB OFFICE ASISTAND)' => 'STAF HUMAS',
    ];

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $delimiter = $this->option('delimiter') ?: $this->detectDelimiter($path);
        $rows = $this->readRows($path, $delimiter);

        if ($rows === []) {
            $this->error('File kosong atau header tidak terbaca.');

            return self::FAILURE;
        }

        $header = array_shift($rows);
        $header = array_map(fn ($h) => $this->normalizeHeader($h), $header);

        $dryRun = (bool) $this->option('dry-run');
        $update = (bool) $this->option('update');

        $workUnits = $this->loadWorkUnits();
        $positions = $this->loadPositions();
        $jenisGtk = $this->loadJenisGtk();

        $stats = [
            'created' => 0, 'updated' => 0, 'skipped' => 0, 'ignored' => 0, 'failed' => 0,
        ];
        $tanpaUnit = [];
        $tanpaJabatan = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $line = $index + 2;

                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $data = [];
                foreach ($header as $i => $key) {
                    $data[$key] = trim((string) ($row[$i] ?? ''));
                }

                $name = $this->cleanName($data['name'] ?? '');

                if ($name === '') {
                    $stats['skipped']++;

                    continue;
                }

                if (in_array($this->normalizeKey($data['satuan kerja'] ?? ''), $this->satuanKerjaSkip, true)) {
                    $stats['ignored']++;

                    continue;
                }

                try {
                    $result = $this->importRow($data, $name, $workUnits, $positions, $jenisGtk, $update, $tanpaUnit, $tanpaJabatan);
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $errors[] = "Baris {$line} ({$name}): ".$e->getMessage();
                }
            }

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Impor dibatalkan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Status', 'Jumlah'], [
            ['Dibuat', $stats['created']],
            ['Diperbarui', $stats['updated']],
            ['Dilewati (sudah ada)', $stats['skipped']],
            ['Diabaikan (BUMP)', $stats['ignored']],
            ['Gagal', $stats['failed']],
        ]);

        if ($tanpaUnit !== []) {
            $this->warn('Satuan kerja tanpa padanan work_units (disimpan sebagai teks saja):');
            foreach ($tanpaUnit as $s => $c) {
                $this->line("  - {$s} ({$c}x)");
            }
        }

        if ($tanpaJabatan !== []) {
            $this->warn('Jabatan tanpa padanan structural_positions (disimpan sebagai teks saja):');
            foreach ($tanpaJabatan as $j => $c) {
                $this->line("  - {$j} ({$c}x)");
            }
        }

        if ($errors !== []) {
            $this->error('Baris gagal:');
            foreach ($errors as $err) {
                $this->line('  - '.$err);
            }
        }

        if ($dryRun) {
            $this->info('Mode dry-run: tidak ada perubahan yang disimpan.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function importRow(array $d, string $name, array $workUnits, array $positions, array $jenisGtk, bool $update, array &$tanpaUnit, array &$tanpaJabatan): string
    {
        $nupy = preg_replace('/\s+/', '', $d['nupy'] ?? '') ?: null;
        $email = strtolower($d['email'] ?? '');

        $user = null;
        if ($nupy) {
            $user = User::whereHas('employment', fn ($q) => $q->where('nupy', $nupy))->first();
        }
        if (! $user && $email !== '') {
            $user = User::where('email', $email)->first();

            // Email dipakai bersama (mis. pasangan); jangan gabungkan dua orang berbeda.
            $existingNupy = $user?->employment?->nupy;
            if ($user && $nupy && $existingNupy && $existingNupy !== $nupy) {
                $user = null;
            }
        }
        if (! $user) {
            $user = User::where('name', $name)->whereDoesntHave('employment')->first();
        }

        $satuanKerja = $this->normalizeKey($d['satuan kerja'] ?? '');
        $jabatanRaw = $this->normalizeKey($d['jabatan'] ?? '');

        $workUnitId = $this->resolveWorkUnit($satuanKerja, $workUnits);
        if ($workUnitId === null && $satuanKerja !== '') {
            $tanpaUnit[$satuanKerja] = ($tanpaUnit[$satuanKerja] ?? 0) + 1;
        }

        $positionName = $this->resolveJabatan($jabatanRaw, $positions, $d['sa'] ?? '');
        if ($positionName === null && $jabatanRaw !== '') {
            $tanpaJabatan[$jabatanRaw] = ($tanpaJabatan[$jabatanRaw] ?? 0) + 1;
        }

        if ($user && ! $update) {
            return 'skipped';
        }

        $status = $user ? 'updated' : 'created';

        if (! $user) {
            $user = new User();
            $user->id = (string) Str::uuid();
            $emailTaken = $email !== '' && User::where('email', $email)->exists();
            $user->email = ($email !== '' && ! $emailTaken) ? $email : $this->fallbackEmail($name, $nupy);
            $user->password = Hash::make('@'.($nupy ?: Str::slug($name)));
        }

        $user->name = $name;
        $user->username = $nupy ?: $user->username;
        $user->no_hp = $this->phone($d['no_hp'] ?? '') ?: $user->no_hp;
        $user->is_active = true;
        $user->must_change_password = $user->exists ? $user->must_change_password : true;
        $user->save();

        $profile = GtkProfile::firstOrNew(['user_id' => $user->id]);
        $profile->fill([
            'nik' => $this->digits($d['nik'] ?? '') ?: null,
            'no_kk' => $this->digits($d['kk_number'] ?? '') ?: null,
            'tempat_lahir' => $d['place_of_birth'] ?: null,
            'tanggal_lahir' => $this->date($d['date_of_birth'] ?? ''),
            'jenis_kelamin' => $this->gender($d['sa'] ?? ''),
            'golongan_darah' => $this->bloodType($d['blood_type'] ?? ''),
            'agama' => 'islam',
            'status_perkawinan' => $this->marital($d['marital_status'] ?? ''),
            'npwp' => $this->npwp($d['npwp'] ?? ''),
        ]);
        $profile->save();

        $statusKepegawaian = $this->employmentStatus($d['employment_status'] ?? '');

        $employment = GtkEmployment::firstOrNew(['user_id' => $user->id]);
        $employment->fill([
            'status_kepegawaian' => $statusKepegawaian,
            'nupy' => $nupy,
            'satuan_kerja' => $d['satuan kerja'] ?: null,
            'jenis_gtk' => $this->jenisGtkLabel($d['jenis gtk'] ?? '', $workUnitId),
            'jenis_gtk_id' => $jenisGtk[$this->jenisGtkLabel($d['jenis gtk'] ?? '', $workUnitId)] ?? null,
            'jabatan' => $d['jabatan'] ?: null,
            'jabatan_id' => $positionName ? ($positions[$positionName]['id'] ?? null) : null,
            'position_type' => $this->positionType($jabatanRaw),
            'is_homeroom' => false,
            'tmt' => $this->date($d['tmt'] ?? ''),
            'nomor_sk' => $d['sk_number'] ?: null,
            'tanggal_sk' => $this->date($d['tanggal sk'] ?? ''),
            'pangkat_golongan' => $d['pangkat gol'] ?: null,
        ]);
        $employment->save();

        if ($workUnitId) {
            GtkWorkUnit::updateOrCreate(
                ['user_id' => $user->id, 'is_primary' => true],
                ['work_unit_id' => $workUnitId, 'jabatan' => $d['jabatan'] ?: null]
            );
        }

        $contact = GtkContact::firstOrNew(['user_id' => $user->id]);
        $contact->fill([
            'no_hp' => $this->phone($d['phone_number'] ?? '') ?: null,
            'no_whatsapp' => $this->phone($d['wa_number'] ?? '') ?: null,
        ]);
        $contact->save();

        if ($this->hasAddress($d)) {
            $address = GtkAddress::firstOrNew(['gtk_profile_id' => $profile->id, 'type' => 'ktp']);
            $address->fill([
                'jalan' => $d['address'] ?: '-',
                'rt_rw' => $this->rtRw($d['rt_rw'] ?? ''),
                'desa' => $d['village'] ?: '-',
                'kecamatan' => $d['district'] ?: '-',
                'kab_kota' => $d['regency'] ?: '-',
                'provinsi' => $d['province'] ?: '-',
                'kode_pos' => '-',
            ]);
            $address->save();
        }

        $this->importEducations($user, $d);

        return $status;
    }

    private function importEducations(User $user, array $d): void
    {
        $entries = [];

        $raw = $d['employee_educations'] ?? '';
        if ($raw !== '') {
            foreach (preg_split('/\s*;\s*/', $raw) as $chunk) {
                $parts = array_map('trim', explode('|', $chunk));
                if (count($parts) < 2) {
                    continue;
                }
                $entries[] = [
                    'jenjang_pendidikan' => $parts[0] ?? '',
                    'jurusan' => $parts[1] ?? '',
                    'fakultas' => $parts[2] ?? '',
                    'nama_satuan_pendidikan' => $parts[4] ?? '',
                    'tahun_masuk' => $parts[5] ?? '',
                    'tahun_lulus' => $parts[6] ?? '',
                ];
            }
        } elseif (($d['nama sekolah'] ?? '') !== '') {
            $entries[] = [
                'jenjang_pendidikan' => $d['jenjang'] ?? '',
                'jurusan' => $d['jurursan'] ?? '',
                'fakultas' => '',
                'nama_satuan_pendidikan' => $d['nama sekolah'],
                'tahun_masuk' => '',
                'tahun_lulus' => $d['tahun lulus'] ?? '',
            ];
        }

        $urutan = 1;
        foreach ($entries as $entry) {
            $jenjang = $this->cleanJenjang($entry['jenjang_pendidikan']);
            $institusi = trim($entry['nama_satuan_pendidikan']);

            if ($jenjang === '' || $institusi === '' || $this->isJunk($jenjang) || $this->isJunk($institusi)) {
                continue;
            }

            $tahunLulus = $this->year($entry['tahun_lulus']);

            GtkEducation::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'jenjang_pendidikan' => $jenjang,
                    'nama_satuan_pendidikan' => $institusi,
                ],
                [
                    'jurusan' => $this->junkToNull($entry['jurusan']),
                    'fakultas' => $this->junkToNull($entry['fakultas']),
                    'tahun_masuk' => $this->year($entry['tahun_masuk']),
                    'tahun_lulus' => $tahunLulus,
                    'status' => 'LULUS',
                    'is_aktif' => true,
                    'is_verified' => false,
                    'urutan' => $urutan++,
                ]
            );
        }
    }

    private function resolveWorkUnit(string $key, array $workUnits): ?string
    {
        if ($key === '') {
            return null;
        }

        if (isset($workUnits['name'][$key])) {
            return $workUnits['name'][$key];
        }

        if (isset($this->satuanKerjaAliases[$key], $workUnits['code'][$this->satuanKerjaAliases[$key]])) {
            return $workUnits['code'][$this->satuanKerjaAliases[$key]];
        }

        foreach ($this->satuanKerjaAliases as $alias => $code) {
            if (str_contains($key, $alias) && isset($workUnits['code'][$code])) {
                return $workUnits['code'][$code];
            }
        }

        $heuristics = [
            'PERPUSTAKAAN' => 'DEP-PERPUS',
            'TAHFIDZ' => 'DEP-TAH',
            'HUMAS' => 'DEP-HUMAS',
            'KEUANGAN' => 'DEP-KEU',
            'KEAMANAN' => 'DEP-SATPAM',
            'UKS' => 'DEP-UKS',
            'TEKNOLOGI INFORMASI' => 'DEP-TIJ',
            'GIZI' => 'DEP-UPGIZI',
            'RUMAH TANGGA' => 'DEP-URT',
            'BAHASA' => 'DEP-BHS',
        ];

        foreach ($heuristics as $needle => $code) {
            if (str_contains($key, $needle) && isset($workUnits['code'][$code])) {
                return $workUnits['code'][$code];
            }
        }

        return null;
    }

    private function resolveJabatan(string $key, array $positions, string $gender): ?string
    {
        if ($key === '') {
            return null;
        }

        $key = str_replace(
            ['STAFF', 'DEPARTMEN ', ' DAN '],
            ['STAF', 'DEPARTEMEN ', ' & '],
            $key
        );

        if ($key === 'STAF UKS') {
            $key = strtoupper($gender) === 'P' ? 'STAF UKS PUTRI' : 'STAF UKS PUTRA';
        }

        if (str_starts_with($key, 'STAF PENGASUHAN')) {
            $key = strtoupper($gender) === 'P' ? 'MUSYRIFAH' : 'MUSYRIF';
        }

        if (isset($positions[$key])) {
            return $key;
        }

        if (isset($this->jabatanAliases[$key], $positions[$this->jabatanAliases[$key]])) {
            return $this->jabatanAliases[$key];
        }

        return null;
    }

    private function loadWorkUnits(): array
    {
        $byName = [];
        $byCode = [];

        foreach (DB::table('work_units')->whereNull('deleted_at')->get(['id', 'name', 'code']) as $unit) {
            $byName[$this->normalizeKey($unit->name)] = $unit->id;
            $byCode[$unit->code] = $unit->id;
        }

        return ['name' => $byName, 'code' => $byCode];
    }

    private function loadPositions(): array
    {
        $positions = [];

        foreach (DB::table('structural_positions')->get(['id', 'name']) as $position) {
            $positions[$this->normalizeKey($position->name)] = ['id' => $position->id];
        }

        return $positions;
    }

    private function loadJenisGtk(): array
    {
        $map = [];

        foreach (JenisGtk::all(['id', 'nama']) as $jenis) {
            $map[$jenis->nama] = $jenis->id;
        }

        return $map;
    }

    private function jenisGtkLabel(string $raw, ?string $workUnitId): ?string
    {
        $raw = $this->normalizeKey($raw);

        $aliases = [
            'PENDIDIK / GURU' => 'Tenaga Kependidikan Satuan Pendidikan',
            'TENAGA ADMINISTRASI & KEUANGAN' => 'Tenaga Keuangan & Akuntansi',
            'PIMPINAN & STRUKTURAL PENDIDIKAN' => 'Pimpinan Pesantren',
        ];

        if (isset($aliases[$raw])) {
            return $aliases[$raw];
        }

        $code = $workUnitId ? DB::table('work_units')->where('id', $workUnitId)->value('code') : null;

        return match (true) {
            str_starts_with((string) $code, 'UAK') => 'Tenaga Kependidikan Satuan Pendidikan',
            str_starts_with((string) $code, 'PNG') => 'Tenaga Pengasuhan & Keasramaan',
            $code === 'DEP-KEU' => 'Tenaga Keuangan & Akuntansi',
            $code === 'DEP-HUMAS' => 'Humas & Personalia',
            $code === 'DEP-UKS' => 'Tenaga Kesehatan UKS',
            $code === 'DEP-SATPAM' => 'Tenaga Keamanan & Ketertiban',
            $code === 'DEP-URT' => 'Tenaga Rumah Tangga & Sarpras',
            $code === 'DEP-TIJ' => 'Tenaga Teknologi Informasi',
            $code === 'DEP-UPGIZI' => 'Tenaga Pelayanan Gizi',
            $code === 'DEP-PERPUS' => 'Tenaga Perpustakaan',
            $code === 'DEP-TAH' => 'Departemen Tahfidz',
            $code === 'DEP-BHS' => 'Departemen Bahasa',
            default => null,
        };
    }

    private function positionType(string $jabatan): string
    {
        return match (true) {
            str_contains($jabatan, 'KEPALA SATUAN PENDIDIKAN') => 'kepala_sekolah',
            str_contains($jabatan, 'WAKIL KEPALA SATUAN PENDIDIKAN') => 'wakasek',
            str_contains($jabatan, 'GURU KELAS') => 'guru_kelas',
            str_contains($jabatan, 'GURU') => 'guru_mapel',
            default => 'tendik',
        };
    }

    private function employmentStatus(string $raw): string
    {
        $raw = $this->normalizeKey($raw);

        return match (true) {
            $raw === '' => 'PTY',
            str_contains($raw, 'PERCOBAAN') => 'Percobaan',
            str_contains($raw, 'MAGANG') => 'Magang',
            str_contains($raw, 'KONTRAK') => 'KONTRAK',
            str_contains($raw, 'GTT') => 'GTT',
            str_contains($raw, 'GTY') => 'GTY',
            str_contains($raw, 'PTT') => 'PTT',
            default => 'PTY',
        };
    }

    private function marital(string $raw): string
    {
        $raw = $this->normalizeKey($raw);

        return match (true) {
            str_contains($raw, 'BELUM') => 'belum_kawin',
            str_contains($raw, 'JANDA/MENINGGAL') => 'cerai_mati',
            str_contains($raw, 'JANDA') => 'janda_cerai',
            str_contains($raw, 'DUDA') => 'duda_cerai',
            str_contains($raw, 'MENIKAH') || str_contains($raw, 'KAWIN') => 'kawin',
            default => 'belum_kawin',
        };
    }

    private function gender(string $raw): ?string
    {
        $raw = strtolower(trim($raw));

        return match ($raw) {
            'l', 'lk', 'laki-laki', 'laki laki' => 'L',
            'p', 'pr', 'perempuan' => 'P',
            default => null,
        };
    }

    private function bloodType(string $raw): ?string
    {
        $raw = strtoupper(trim($raw));

        return in_array($raw, ['A', 'B', 'AB', 'O'], true) ? $raw : null;
    }

    private function date(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || $this->isJunk($raw)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function year(string $raw): ?int
    {
        $raw = trim($raw);
        if (! preg_match('/^(19|20)\d{2}$/', $raw)) {
            return null;
        }

        return (int) $raw;
    }

    private function phone(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || $this->isJunk($raw)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === null || strlen($digits) < 8) {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return substr($digits, 0, 20);
    }

    private function digits(string $raw): string
    {
        $raw = trim($raw);
        if ($this->isJunk($raw)) {
            return '';
        }

        return preg_replace('/\D/', '', $raw) ?? '';
    }

    private function npwp(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || $this->isJunk($raw)) {
            return null;
        }

        return $raw;
    }

    private function rtRw(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '/') {
            return '-';
        }

        return substr($raw, 0, 10);
    }

    private function hasAddress(array $d): bool
    {
        return trim(($d['address'] ?? '').($d['village'] ?? '').($d['district'] ?? '')) !== '';
    }

    private function cleanName(string $raw): string
    {
        return trim(preg_replace('/\s+/', ' ', $raw) ?? '');
    }

    private function cleanJenjang(string $raw): string
    {
        $raw = $this->normalizeKey($raw);

        return match (true) {
            str_contains($raw, 'S3') => 'S3',
            str_contains($raw, 'S2') => 'S2',
            str_contains($raw, 'D4') || str_contains($raw, 'S1') => 'S1',
            str_contains($raw, 'D3') => 'D3',
            str_contains($raw, 'D2') => 'D2',
            str_contains($raw, 'D1') => 'D1',
            str_contains($raw, 'SMA') || str_contains($raw, 'SMK') || str_contains($raw, 'MA') => 'SMA',
            str_contains($raw, 'SMP') || str_contains($raw, 'MTS') => 'SMP',
            str_contains($raw, 'SD') || str_contains($raw, 'MI') => 'SD',
            str_contains($raw, 'PONDOK') => 'Pondok',
            default => '',
        };
    }

    private function isJunk(string $value): bool
    {
        $value = strtoupper(trim($value));

        return $value === ''
            || in_array($value, ['-', '/', '00', '0', '#VALUE!', 'TIDAK ADA', 'TIDAK', '__________________', '_________'], true);
    }

    private function junkToNull(string $value): ?string
    {
        return $this->isJunk($value) ? null : trim($value);
    }

    private function fallbackEmail(string $name, ?string $nupy): string
    {
        $slug = Str::slug($name) ?: 'gtk';

        return $nupy ? strtolower($nupy).'@gtk.alim.local' : $slug.'@gtk.alim.local';
    }

    private function normalizeKey(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = str_replace(['\u{00A0}', '\t'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return $value;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;
        $header = strtolower(trim($header));
        $header = preg_replace('/\s+/', ' ', $header) ?? '';

        return match ($header) {
            'nama', 'name' => 'name',
            'email' => 'email',
            'nik' => 'nik',
            'kk_number', 'no kk', 'no_kk' => 'kk_number',
            'place_of_birth', 'tempat lahir' => 'place_of_birth',
            'date_of_birth', 'tanggal lahir' => 'date_of_birth',
            'sa', 'jenis kelamin', 'jk' => 'sa',
            'blood_type', 'gol darah', 'golongan darah' => 'blood_type',
            'agama' => 'agama',
            'marital_status', 'status perkawinan' => 'marital_status',
            'npwp' => 'npwp',
            'phone_number', 'no hp' => 'phone_number',
            'wa_number', 'no wa' => 'wa_number',
            'nupy' => 'nupy',
            'jenis gtk' => 'jenis gtk',
            'jabatan' => 'jabatan',
            'employment_status', 'status kepegawaian' => 'employment_status',
            'tmt' => 'tmt',
            'sk_number', 'no sk' => 'sk_number',
            'tanggal sk' => 'tanggal sk',
            'pangkat gol', 'pangkat golongan' => 'pangkat gol',
            'nama sekolah' => 'nama sekolah',
            'jurursan', 'jurusan' => 'jurursan',
            'tahun lulus' => 'tahun lulus',
            'address', 'alamat' => 'address',
            'rt_rw', 'rt/rw' => 'rt_rw',
            'village', 'desa' => 'village',
            'district', 'kecamatan' => 'district',
            'regency', 'kab/kota' => 'regency',
            'province', 'provinsi' => 'province',
            'employee_educations', 'riwayat pendidikan' => 'employee_educations',
            'satuan kerja', 'satuan_kerja' => 'satuan kerja',
            default => $header,
        };
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $first = (string) fgets($handle);
        fclose($handle);

        return str_contains($first, "\t") ? "\t" : (str_contains($first, ';') ? ';' : ',');
    }

    private function readRows(string $path, string $delimiter): array
    {
        $handle = fopen($path, 'r');
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
