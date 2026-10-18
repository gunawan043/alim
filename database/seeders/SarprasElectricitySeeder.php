<?php

namespace Database\Seeders;

use App\Models\ElectricityMeter;
use App\Models\User;
use Illuminate\Database\Seeder;

class SarprasElectricitySeeder extends Seeder
{
    public function run(): void
    {
        $createdBy = User::query()->where('is_system_admin', true)->value('id') ?? User::query()->value('id');

        $meters = [
            ['MA PLUS ABU HURAIRAH (3 PHASE)', 'prabayar', '32900232805', 'GENSET', 2000000, 2],
            ['KESRA PONPES ABU HURAIRAH', 'prabayar', '32188847860', 'GENSET', 500000, 2],
            ['SMPIT PUTRA MATARAM (3 PHASE)', 'prabayar', '32902805459', 'GENSET', 2200000, 2],
            ['SDIT ABU HURAIARAH', 'prabayar', '1123379040', 'GENSET', 900000, 2],
            ['PONPES ABU HURAIRAH (PERIKSA)', 'prabayar', '56801579261', 'GENSET', 1000000, 2],
            ['SMP ISLAM TERPADU', 'prabayar', '86242869443', 'GENSET', 1500000, 2],
            ['PP ABU HURAIRAH', 'prabayar', '86246273592', 'GENSET', 1300000, 2],
            ['SMA IT MATARAM (3 PHASE)', 'prabayar', '32902655276', 'GENSET', 1200000, 2],
            ['MA PLUS ABU HURAIRAH MTR', 'prabayar', '14338336390', 'MINIMART', 2500000, 2],
            ['PONPES ABU HURAIRAH MATARAM (BARU)', 'pascabayar', '441503618209', 'GARDU AC', null, 5],
            ['PP ABU HURAIRAH MATARAM (LAMA)', 'pascabayar', '441502738354', 'GARDU AC', null, 5],
        ];

        foreach ($meters as [$name, $type, $number, $location, $amount, $dueDay]) {
            ElectricityMeter::updateOrCreate(
                ['meter_number' => $number],
                [
                    'name' => $name,
                    'meter_type' => $type,
                    'location' => $location,
                    'default_amount' => $amount,
                    'payment_due_day' => $dueDay,
                    'is_active' => true,
                    'notes' => $type === 'prabayar'
                        ? 'Pengisian token paling lambat tanggal 2 setiap bulan.'
                        : 'Pembayaran tagihan setiap tanggal 5.',
                    'created_by' => $createdBy,
                ]
            );
        }

        $this->command?->info('SarprasElectricitySeeder: '.count($meters).' meter listrik disiapkan.');
    }
}
