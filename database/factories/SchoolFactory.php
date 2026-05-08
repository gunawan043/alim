<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $levels = ['sd', 'smp', 'sma', 'smk'];
        $statuses = ['negeri', 'swasta'];
        $akreditasi = ['A', 'B', 'C', null];
        $opsHours = ['pagi', 'siang', 'full_day', null];
        $provinces = ['32', '31', '36', '35', '33']; // Jawa Barat, DKI Jakarta, Banten, Jawa Timur, Jawa Tengah

        $provinceCode = $this->faker->randomElement($provinces);
        $cityMapping = [
            '32' => ['3271', '3272', '3214'], // Bandung, Bekasi, Bogor
            '31' => ['3171', '3172'],          // Jakarta Pusat, Jakarta Selatan
            '36' => ['3671', '3672'],          // Tangerang, Serang
            '35' => ['3571', '3572'],          // Surabaya, Sidoarjo
            '33' => ['3371', '3372'],          // Semarang, Solo
        ];

        $cityCode = $this->faker->randomElement($cityMapping[$provinceCode] ?? ['3271']);
        $level = $this->faker->randomElement($levels);

        return [
            'id' => (string) Str::uuid(),
            'work_unit_id' => null,
            'school_code' => 'SKH-' . strtoupper($this->faker->lexify('???')),
            'npsn' => $this->faker->numerify('##########'),
            'nss' => $this->faker->numerify('############'),
            'name' => $this->generateSchoolName($level),
            'address' => $this->faker->streetAddress() . ', ' . $this->faker->city(),
            'province_code' => $provinceCode,
            'city_code' => $cityCode,
            'district_code' => $cityCode . $this->faker->numerify('##'),
            'village_code' => $cityCode . $this->faker->numerify('###'),
            'postal_code' => $this->faker->postcode2(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'website' => 'https://' . $this->faker->domainName(),
            'school_level' => $level,
            'school_status' => $this->faker->randomElement($statuses),
            'accreditation' => $this->faker->randomElement($akreditasi),
            'accreditation_year' => $this->faker->randomElement([2018, 2019, 2020, 2021, 2022, 2023]),
            'principal_name' => $this->faker->name('male'),
            'principal_nip' => $this->faker->numerify('################'),
            'operational_hours' => $this->faker->randomElement($opsHours),
            'established_date' => $this->faker->dateTimeBetween('1990-01-01', '2015-12-31'),
            'established_decree' => 'SK. ' . $this->faker->randomElement(['DIKMENDAS', 'MENBUD', 'MENRISTEK']) . '/' . $this->faker->numerify('####/2020'),
            'land_area' => $this->faker->randomFloat(2, 500, 10000),
            'building_area' => $this->faker->randomFloat(2, 300, 5000),
            'is_active' => $this->faker->boolean(85),
            'logo_path' => null,
            'kop_path' => null,
            'ttd_ksp_path' => null,
            'stamp_path' => null,
            'kop_nama' => null,
            'kop_alamat' => null,
            'kop_telp' => null,
            'kop_email' => null,
            'kop_website' => null,
            'kop_npsn' => null,
            'kopsis_active' => true,
            'bank_name' => $this->faker->randomElement(['Bank Negara Indonesia', 'Bank Mandiri', 'Bank BRI', 'Bank BTPN', null]),
            'bank_cabang' => $this->faker->city() . ' Branch',
            'bank_rekening' => $this->faker->numerify('###########'),
            'bank_an' => $this->faker->name(),
            'npwp' => $this->faker->numerify('##.###.###.#-###.###'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function sd(): static
    {
        return $this->state(fn (array $attributes) => ['school_level' => 'sd']);
    }

    public function smp(): static
    {
        return $this->state(fn (array $attributes) => ['school_level' => 'smp']);
    }

    public function sma(): static
    {
        return $this->state(fn (array $attributes) => ['school_level' => 'sma']);
    }

    public function smk(): static
    {
        return $this->state(fn (array $attributes) => ['school_level' => 'smk']);
    }

    public function negeri(): static
    {
        return $this->state(fn (array $attributes) => ['school_status' => 'negeri']);
    }

    public function swasta(): static
    {
        return $this->state(fn (array $attributes) => ['school_status' => 'swasta']);
    }

    private function generateSchoolName(string $level): string
    {
        $prefixes = [
            'sd'  => ['SD Negeri', 'SD Islam', 'SD Kristen', 'SD Katolik', 'SD Tunas', 'SD Permata', 'SD Widya', 'SD Bintang'],
            'smp' => ['SMP Negeri', 'SMP Islam', 'SMP Kristen', 'SMP Katolik', 'SMP Tunas', 'SMP Widya', 'SMP Bintang', 'SMP Cendekia'],
            'sma' => ['SMA Negeri', 'SMA Islam', 'SMA Kristen', 'SMA Katolik', 'SMA Tunas', 'SMA Widya', 'SMA Bintang', 'SMA Cendekia'],
            'smk' => ['SMK Negeri', 'SMK Islam', 'SMK Teknologi', 'SMK Bisnis', 'SMK Kesehatan', 'SMK Pariwisata', 'SMK Negeri', 'SMK Cendekia'],
        ];

        $suffixes = [
            ' 1', ' 2', ' 3', ' 4', ' 5',
            ' Utama', ' Mulia', ' Jaya', ' Harapan', ' Gemilang',
            ' Cendekia', ' Kreatif', ' Unggul', ' Persada',
        ];

        $prefix = $this->faker->randomElement($prefixes[$level]);
        $suffix = $this->faker->randomElement($suffixes);

        return $prefix . $suffix;
    }
}
