<?php

namespace App\Services\Dashboard;

use App\Models\User;

/**
 * Menyusun KPI, grafik, temuan, dan tautan drill-down per jabatan.
 *
 * KPI dan temuan memakai angka dari DashboardAnalyticsService — tidak ada
 * narasi yang tidak didukung angka.
 */
class DashboardPresenter
{
    public function __construct(private DashboardAnalyticsService $analytics) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{kpis: array, charts: array, findings: array, drilldowns: array}
     */
    public function present(string $roleSlug, array $data, array $filters, User $user): array
    {
        $kpis = $this->kpis($roleSlug, $data, $filters, $user);
        $findings = $this->findings($roleSlug, $data, $filters, $user);

        return [
            'kpis' => $kpis,
            'charts' => $this->charts($roleSlug, $data),
            'findings' => $findings,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function kpis(string $role, array $data, array $filters, User $user): array
    {
        $students = $data['students'] ?? [];
        $gtk = $data['gtk'] ?? [];
        $mutations = $data['mutations'] ?? [];
        $rombel = $data['rombel'] ?? [];
        $attendance = $data['attendance'] ?? [];
        $academic = $data['academic_health'] ?? [];
        $finance = $data['finance'] ?? [];
        $boarding = $data['boarding'] ?? [];
        $tahfidz = $data['tahfidz'] ?? [];
        $health = $data['health'] ?? [];
        $sdm = $data['sdm'] ?? [];
        $sarpras = $data['sarpras'] ?? [];
        $system = $data['system'] ?? [];
        $teacher = $data['teacher'] ?? [];
        $homeroom = $data['homeroom'] ?? [];

        $report = fn (string $route, array $extra = []) => route($route, array_merge([
            'userId' => $user->id,
            'academic_year_id' => $filters['academic_year_id'],
        ], array_filter($extra)));

        $studentKpis = [
            $this->kpi('Santri Aktif', $students['total'] ?? null, 'ri-user-3-line', 'primary', 'Santri berstatus aktif', $report('user.laporan.santri')),
            $this->kpi('Santri Baru', $students['new'] ?? null, 'ri-user-add-line', 'success', 'Periode terpilih', $report('user.laporan.santri'), $students['growth_rate'] ?? null, 'Belum ada data pendaftaran'),
            $this->kpi('Mutasi Masuk (12 Bln)', $mutations['total_in'] ?? null, 'ri-login-box-line', 'info', '12 bulan terakhir', $report('user.laporan.populasi.mutasi')),
            $this->kpi('Mutasi Keluar (12 Bln)', $mutations['total_out'] ?? null, 'ri-logout-box-line', 'danger', '12 bulan terakhir', $report('user.laporan.populasi.mutasi')),
            $this->kpi('Pertumbuhan Bersih', $mutations['net'] ?? null, 'ri-line-chart-line', ($mutations['net'] ?? 0) < 0 ? 'danger' : 'success', 'Masuk − keluar', $report('user.laporan.populasi.pertumbuhan')),
            $this->kpi('Kehadiran Hari Ini', $attendance['today_rate'] ?? null, 'ri-calendar-check-line', 'success', 'Persen hadir', $report('user.laporan.akademik.kehadiran'), null, 'Presensi belum diisi'),
        ];

        return match ($role) {
            'pimpinan' => array_merge($studentKpis, [
                $this->kpi('GTK Aktif', $gtk['total'] ?? null, 'ri-team-line', 'primary', 'Seluruh unit', $report('user.laporan.gtk')),
                $this->kpi('Kehadiran GTK', null, 'ri-fingerprint-line', 'info', 'Belum tersedia', null, null, 'Data presensi GTK belum tersedia'),
            ]),
            'satuan-pendidikan' => array_merge($studentKpis, [
                $this->kpi('GTK', $gtk['total'] ?? null, 'ri-team-line', 'primary', 'Guru & tendik'),
                $this->kpi('Rombel', $rombel['total'] ?? null, 'ri-building-2-line', 'info', 'Rombel aktif'),
                $this->kpi('Rombel Tanpa Wali', $rombel['without_homeroom'] ?? null, 'ri-user-unfollow-line', 'warning', 'Perlu penetapan'),
                $this->kpi('Nilai Belum Lengkap', $academic['nilai_belum_lengkap'] ?? null, 'ri-file-warning-line', 'danger', 'Baris nilai kosong'),
            ]),
            'personalia' => [
                $this->kpi('GTK Aktif', $gtk['total'] ?? null, 'ri-team-line', 'primary', 'Guru & tendik', $report('user.laporan.gtk')),
                $this->kpi('GTK Belum Verifikasi', $gtk['pending'] ?? null, 'ri-shield-check-line', 'warning', 'Perlu ditinjau'),
                $this->kpi('Kontrak Berakhir ≤60 hari', $gtk['contract_ending'] ?? null, 'ri-file-list-3-line', 'danger', 'Perlu perpanjangan'),
                $this->kpi('Cuti Aktif', $sdm['cuti_aktif'] ?? null, 'ri-calendar-event-line', 'info', 'Hari ini'),
            ],
            'keuangan' => [
                $this->kpi('Tagihan Bulan Ini', $finance['tagihan'] ?? null, 'ri-file-list-3-line', 'primary', 'Total tagihan'),
                $this->kpi('Terbayar', $finance['terbayar'] ?? null, 'ri-money-dollar-circle-line', 'success', 'Total penerimaan'),
                $this->kpi('Tunggakan', $finance['tunggakan'] ?? null, 'ri-error-warning-line', 'danger', 'Tagihan belum lunas'),
                $this->kpi('Rasio Penerimaan', $finance['collection_rate'] ?? null, 'ri-pie-chart-line', 'info', 'Persen'),
            ],
            'asrama' => [
                $this->kpi('Santri Asrama', $boarding['santri'] ?? null, 'ri-home-smile-2-line', 'primary', 'Penghuni aktif'),
                $this->kpi('Kamar Aktif', $boarding['kamar'] ?? null, 'ri-door-open-line', 'info', 'Kamar tersedia'),
                $this->kpi('Izin Aktif', $boarding['izin_aktif'] ?? null, 'ri-walk-line', 'success', 'Sedang di luar'),
                $this->kpi('Pelanggaran Bulan Ini', $boarding['pelanggaran_bulan'] ?? null, 'ri-shield-star-line', 'danger', 'Catatan tata tertib'),
            ],
            'tahfidz' => [
                $this->kpi('Halaqah Aktif', $tahfidz['halaqah'] ?? null, 'ri-group-line', 'primary', 'Kelompok aktif'),
                $this->kpi('Setoran Hari Ini', $tahfidz['setoran_hari_ini'] ?? null, 'ri-book-2-line', 'success', 'Setoran tercatat'),
                $this->kpi('Santri Aktif', $students['total'] ?? null, 'ri-user-3-line', 'info', 'Santri unit'),
            ],
            'uks' => [
                $this->kpi('Kunjungan Hari Ini', $health['kunjungan_hari_ini'] ?? null, 'ri-hospital-line', 'primary', 'Kunjungan UKS'),
                $this->kpi('Rujukan Bulan Ini', $health['rujukan_bulan'] ?? null, 'ri-ambulance-line', 'danger', 'Kasus dirujuk'),
                $this->kpi('Santri Aktif', $students['total'] ?? null, 'ri-user-heart-line', 'info', 'Populasi unit'),
            ],
            'unit-rumah-tangga' => [
                $this->kpi('Tiket Terbuka', $sarpras['tiket_terbuka'] ?? null, 'ri-customer-service-2-line', 'primary', 'Belum selesai'),
                $this->kpi('Tiket Lewat SLA', $sarpras['tiket_lewat_sla'] ?? null, 'ri-timer-flash-line', 'danger', '> 7 hari'),
                $this->kpi('Aset Rusak', $sarpras['aset_rusak'] ?? null, 'ri-archive-2-line', 'warning', 'Perlu perbaikan'),
            ],
            'super-admin' => [
                $this->kpi('Pengguna', $system['pengguna'] ?? null, 'ri-group-line', 'primary', 'Total akun'),
                $this->kpi('Pengguna Aktif', $system['aktif'] ?? null, 'ri-user-follow-line', 'success', 'Akun aktif'),
                $this->kpi('Tanpa Role', $system['tanpa_role'] ?? null, 'ri-user-unfollow-line', 'warning', 'Perlu penetapan akses'),
                $this->kpi('Failed Job', $system['failed_job'] ?? null, 'ri-error-warning-line', 'danger', 'Antrean gagal'),
                $this->kpi('Antrean Job', $system['antrean'] ?? null, 'ri-stack-line', 'info', 'Menunggu proses'),
                $this->kpi('Aktivitas Hari Ini', $system['aktivitas_hari_ini'] ?? null, 'ri-history-line', 'primary', 'Log sistem'),
            ],
            'guru' => [
                $this->kpi('Buku Ajar', $teacher['books'] ?? null, 'ri-book-3-line', 'primary', 'Buku aktif'),
                $this->kpi('Santri Diajar', $teacher['students'] ?? null, 'ri-user-3-line', 'info', 'Santri unik'),
                $this->kpi('Nilai Belum Lengkap', $teacher['nilai_kosong'] ?? null, 'ri-file-warning-line', 'danger', 'Baris nilai kosong'),
                $this->kpi('Jurnal Kosong', $teacher['jurnal_kosong'] ?? null, 'ri-article-line', 'warning', 'Buku tanpa jurnal'),
            ],
            'wali-kelas' => [
                $this->kpi('Kelas Binaan', $homeroom['classes'] ?? null, 'ri-building-2-line', 'primary', 'Rombel aktif'),
                $this->kpi('Santri Kelas', $homeroom['students'] ?? null, 'ri-user-3-line', 'info', 'Santri unik'),
                $this->kpi('Kehadiran Hari Ini', $attendance['today_rate'] ?? null, 'ri-calendar-check-line', 'success', 'Persen hadir', null, null, 'Presensi belum diisi'),
            ],
            default => $studentKpis,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findings(string $role, array $data, array $filters, User $user): array
    {
        $findings = [];
        $rombel = $data['rombel'] ?? [];
        $academic = $data['academic_health'] ?? [];
        $gtk = $data['gtk'] ?? [];
        $sarpras = $data['sarpras'] ?? [];
        $system = $data['system'] ?? [];
        $teacher = $data['teacher'] ?? [];
        $homeroom = $data['homeroom'] ?? [];

        $report = fn (string $route, array $extra = []) => route($route, array_merge([
            'userId' => $user->id,
            'academic_year_id' => $filters['academic_year_id'],
        ], array_filter($extra)));

        if (($rombel['without_homeroom'] ?? 0) > 0) {
            $findings[] = [
                'title' => 'Rombel tanpa wali kelas',
                'description' => 'Rombel aktif yang belum memiliki wali kelas dapat menghambat publikasi rapor dan tindak lanjut.',
                'count' => $rombel['without_homeroom'],
                'severity' => 'danger',
                'url' => route('user.study-groups.index', ['userId' => $user->id]),
                'rule' => 'study_groups.homeroom_teacher_id kosong',
            ];
        }

        if (($rombel['over_capacity'] ?? 0) > 0) {
            $findings[] = [
                'title' => 'Rombel melebihi kapasitas',
                'description' => 'Jumlah santri melampaui kapasitas rombel yang tercatat.',
                'count' => $rombel['over_capacity'],
                'severity' => 'warning',
                'rule' => 'santri aktif > kapasitas rombel',
            ];
        }

        if (($academic['jurnal_kosong'] ?? 0) > 0) {
            $findings[] = [
                'title' => 'Buku ajar tanpa jurnal',
                'description' => 'Buku pengajaran aktif yang belum memiliki catatan jurnal pembelajaran.',
                'count' => $academic['jurnal_kosong'],
                'severity' => 'warning',
                'url' => $report('user.laporan.akademik.keterlaksanaan'),
                'rule' => 'tidak ada baris admin_jurnal_pembelajaran',
            ];
        }

        if (($gtk['contract_ending'] ?? 0) > 0) {
            $findings[] = [
                'title' => 'Kontrak GTK berakhir dalam 60 hari',
                'description' => 'Perlu ditinjau untuk perpanjangan atau proses rekrutmen pengganti.',
                'count' => $gtk['contract_ending'],
                'severity' => 'warning',
                'rule' => 'gtk_employments.end_date ≤ hari ini + 60 hari',
            ];
        }

        if (($sarpras['tiket_lewat_sla'] ?? 0) > 0) {
            $findings[] = [
                'title' => 'Tiket perbaikan melewati SLA',
                'description' => 'Tiket terbuka lebih dari 7 hari tanpa penyelesaian.',
                'count' => $sarpras['tiket_lewat_sla'],
                'severity' => 'danger',
                'rule' => 'status belum selesai dan created_at > 7 hari',
            ];
        }

        if (($system['failed_job'] ?? 0) > 0) {
            $findings[] = [
                'title' => 'Job gagal diproses',
                'description' => 'Proses latar belakang gagal dan perlu ditinjau pada log antrean.',
                'count' => $system['failed_job'],
                'severity' => 'danger',
                'rule' => 'failed_jobs memiliki baris',
            ];
        }

        if (($system['tanpa_role'] ?? 0) > 0) {
            $findings[] = [
                'title' => 'Akun tanpa role',
                'description' => 'Pengguna tanpa role tidak dapat mengakses modul sesuai jabatan.',
                'count' => $system['tanpa_role'],
                'severity' => 'warning',
                'rule' => 'users tanpa model_has_roles',
            ];
        }

        return $findings;
    }

    /**
     * Grafik standar yang seragam antarjabatan.
     *
     * @return array<string, array<string, mixed>>
     */
    private function charts(string $role, array $data): array
    {
        $attendance = $data['attendance']['series'] ?? [];
        $mutations = $data['mutations']['series'] ?? [];

        return [
            'attendance' => [
                'title' => 'Tren Kehadiran Santri (14 Hari)',
                'type' => 'line',
                'categories' => array_column($attendance, 'label'),
                'series' => [
                    ['name' => 'Hadir %', 'data' => array_map(fn ($row) => $row['rate'], $attendance)],
                ],
                'empty' => 'Presensi harian belum tersedia pada periode ini.',
            ],
            'mutations' => [
                'title' => 'Mutasi Masuk & Keluar (12 Bulan)',
                'type' => 'bar',
                'categories' => array_column($mutations, 'label'),
                'series' => [
                    ['name' => 'Masuk', 'data' => array_column($mutations, 'masuk')],
                    ['name' => 'Keluar', 'data' => array_column($mutations, 'keluar')],
                ],
                'colors' => ['#0ab39c', '#f06548'],
                'empty' => 'Belum ada mutasi resmi pada 12 bulan terakhir.',
            ],
        ];
    }

    private function kpi(string $label, $value, string $icon, string $color, ?string $note = null, ?string $url = null, ?float $compare = null, ?string $empty = null): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'icon' => $icon,
            'color' => $color,
            'note' => $note,
            'url' => $url,
            'compare' => $compare,
            'empty' => $empty,
        ];
    }
}
