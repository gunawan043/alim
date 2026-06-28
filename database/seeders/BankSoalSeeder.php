<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BankSoalSeeder extends Seeder
{
    public function run(): void
    {
        $matematika = Subject::where('name', 'Matematika')->first();
        if (! $matematika) {
            $this->command->warn('Subject Matematika tidak ditemukan, skip BankSoalSeeder.');

            return;
        }

        $tps = TujuanPembelajaran::where('subject_id', $matematika->id)->get();
        if ($tps->isEmpty()) {
            $this->command->warn('TP untuk Matematika kosong, skip BankSoalSeeder.');

            return;
        }

        $banks = [
            [
                'school_id' => $matematika->school_id,
                'subject_id' => $matematika->id,
                'fase' => 'E',
                'nama' => 'Bank Soal Matematika Fase E — Gasal 2026/2027',
                'deskripsi' => 'Bank soal untuk STS dan SAS Matematika SMA Fase E semester gasal.',
                'jenis_soal' => 'campuran',
                'tingkat_kesulitan_target' => 'campuran',
                'is_public' => true,
                'owner_user_id' => null,
                'shared_scope' => 'internal_school',
                'tp_ids' => $tps->pluck('id')->toArray(),
            ],
            [
                'school_id' => $matematika->school_id,
                'subject_id' => $matematika->id,
                'fase' => 'E',
                'nama' => 'Bank Soal Ujian Harian Matematika',
                'deskripsi' => 'Bank untuk ulangan harian per KD.',
                'jenis_soal' => 'pilihan_ganda',
                'tingkat_kesulitan_target' => 'sedang',
                'is_public' => true,
                'owner_user_id' => null,
                'shared_scope' => 'internal_school',
                'tp_ids' => $tps->take(2)->pluck('id')->toArray(),
            ],
        ];

        $count = 0;
        foreach ($banks as $data) {
            $tpIds = $data['tp_ids'];
            unset($data['tp_ids']);

            $existing = BankSoal::where('subject_id', $data['subject_id'])
                ->where('nama', $data['nama'])
                ->first();

            $bank = $existing ?: new BankSoal;
            $bank->fill(array_merge($data, ['id' => $bank->id ?: (string) Str::uuid()]));
            $bank->save();

            if (method_exists($bank, 'tujuanPembelajaran')) {
                $bank->tujuanPembelajaran()->sync($tpIds);
            }

            $count++;
            $this->command->info("BankSoal: {$bank->nama} (TP attached: ".count($tpIds).')');
        }

        $this->command->info("[BankSoalSeeder] Done. created/updated: {$count}");
    }
}
