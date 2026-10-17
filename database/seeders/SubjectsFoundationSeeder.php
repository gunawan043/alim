<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectsFoundationSeeder extends Seeder
{
    /**
     * Mapel pondok pesantren Abu Hurairah.
     * Dipisah ke kategori agar mudah dikembangkan.
     */
    private array $subjects = [
        // Mapel Nasional
        ['code' => 'PP', 'name' => 'Pendidikan Pancasila', 'category' => 'nasional', 'credit_hours' => 2],
        ['code' => 'INDO', 'name' => 'Bahasa Indonesia', 'category' => 'nasional', 'credit_hours' => 6],
        ['code' => 'MTK', 'name' => 'Matematika', 'category' => 'nasional', 'credit_hours' => 6],
        ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'category' => 'nasional', 'credit_hours' => 4],
        ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'category' => 'nasional', 'credit_hours' => 4],
        ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani Olahraga dan Kesehatan', 'category' => 'nasional', 'credit_hours' => 4],
        ['code' => 'SBK', 'name' => 'Seni Budaya dan Ketrampilan', 'category' => 'nasional', 'credit_hours' => 2],
        ['code' => 'INF', 'name' => 'Informatika', 'category' => 'nasional', 'credit_hours' => 2],

        // Mapel Pendidikan Agama Islam
        ['code' => 'AGM', 'name' => 'Pendidikan Agama Islam', 'category' => 'nasional', 'credit_hours' => 6],
        ['code' => 'AQD', 'name' => 'Aqidah Akhlak', 'category' => 'nasional', 'credit_hours' => 3],
        ['code' => 'FQH', 'name' => 'Fiqh', 'category' => 'nasional', 'credit_hours' => 3],
        ['code' => 'HDTS', 'name' => 'Hadits', 'category' => 'nasional', 'credit_hours' => 2],
        ['code' => 'TWD', 'name' => 'Tauhid', 'category' => 'nasional', 'credit_hours' => 2],
        ['code' => 'SKI', 'name' => 'Sejarah Kebudayaan Islam', 'category' => 'nasional', 'credit_hours' => 2],
        ['code' => 'THF', 'name' => 'Tahfidz Al-Quran', 'category' => 'nasional', 'credit_hours' => 4],

        // Mapel Bahasa Arab
        ['code' => 'AR01', 'name' => 'Bahasa Arab Dasar', 'category' => 'nasional', 'credit_hours' => 4],
        ['code' => 'NHW', 'name' => 'Nahwu', 'category' => 'nasional', 'credit_hours' => 3],
        ['code' => 'SHF', 'name' => 'Sharaf', 'category' => 'nasional', 'credit_hours' => 3],
        ['code' => 'MHD', 'name' => 'Muhadatsah', 'category' => 'nasional', 'credit_hours' => 3],
        ['code' => 'MFT', 'name' => 'Mufradat', 'category' => 'nasional', 'credit_hours' => 2],
        ['code' => 'BLGH', 'name' => 'Balaghah', 'category' => 'nasional', 'credit_hours' => 2],

        // Mapel Bahasa Inggris
        ['code' => 'ENG01', 'name' => 'Bahasa Inggris', 'category' => 'nasional', 'credit_hours' => 4],
        ['code' => 'ENG02', 'name' => 'English for Daily Communication', 'category' => 'nasional', 'credit_hours' => 2],

        // Mapel Lokal / Keunggulan Pondok
        ['code' => 'JAW', 'name' => 'Bahasa Jawa', 'category' => 'muatan_lokal', 'credit_hours' => 2],
        ['code' => 'QKL', 'name' => 'Qiroatul Kutub', 'category' => 'muatan_lokal', 'credit_hours' => 2],
        ['code' => 'SRH', 'name' => 'Sirah Nabawiyah', 'category' => 'muatan_lokal', 'credit_hours' => 2],
        ['code' => 'PRAK', 'name' => 'Prakarya', 'category' => 'muatan_lokal', 'credit_hours' => 2],
        ['code' => 'EKS', 'name' => 'Ekstrakurikuler', 'category' => 'muatan_lokal', 'credit_hours' => 2],
        ['code' => 'PMT', 'name' => 'Pembinaan Mental', 'category' => 'muatan_lokal', 'credit_hours' => 2],
    ];

    public function run(): void
    {
        School::query()->cursor()->each(function (School $school): bool {
            foreach ($this->subjects as $subject) {
                Subject::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'code' => $subject['code'],
                    ],
                    [
                        'name' => $subject['name'],
                        'category' => $subject['category'],
                        'credit_hours' => $subject['credit_hours'],
                        'is_active' => true,
                    ]
                );
            }

            return true;
        });
    }
}
