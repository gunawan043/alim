{{-- Akses harian GTK Satuan Pendidikan — ditampilkan di atas widget dashboard. --}}
@php
    $dashboardUser = $user ?? auth()->user();
    $userId = $dashboardUser?->id;
    $jabatanKey = strtolower((string) ($jabatanCode ?? ''));
    $tugas = collect($tugasTambahan ?? [])->map(fn ($item) => strtolower((string) $item));

    $isGuruDashboard = str_contains($jabatanKey, 'guru');
    $isTahfidzDashboard = str_contains($jabatanKey, 'tahfidz')
        || $tugas->contains(fn ($item) => str_contains($item, 'tahfidz'));
    $isWaliDashboard = $tugas->contains(fn ($item) => str_contains($item, 'wali kelas'));
    $isStrukturalDashboard = str_contains($jabatanKey, 'kepala')
        || str_contains($jabatanKey, 'wakil')
        || $tugas->contains(fn ($item) => str_contains($item, 'kurikulum') || str_contains($item, 'kesiswaan'));
    $isTuDashboard = str_contains($jabatanKey, 'tata_usaha') || str_contains($jabatanKey, 'tu_sekolah');
    $isSupervisorDashboard = $isStrukturalDashboard
        || $tugas->contains(fn ($item) => str_contains($item, 'supervisor') || str_contains($item, 'kurikulum'));

    $links = [];
    $addLink = function (bool $condition, string $routeName, string $label, string $icon, string $color, array $params = []) use (&$links, $userId): void {
        if (! $condition || ! $userId || ! \Illuminate\Support\Facades\Route::has($routeName)) {
            return;
        }

        $links[] = [
            'url' => route($routeName, array_merge(['userId' => $userId], $params)),
            'label' => $label,
            'icon' => $icon,
            'color' => $color,
        ];
    };

    if ($isGuruDashboard) {
        $addLink(true, 'user.teacher-qr.scan', 'Scan QR Kehadiran', 'ri-qr-scan-2-line', 'primary');
        $addLink(true, 'user.teacher-qr.history', 'Riwayat Kehadiran', 'ri-history-line', 'info');
        $addLink(true, 'user.schools.guru-mapel.index', 'Buku Nilai Saya', 'ri-book-3-line', 'success');
        $addLink(true, 'user.jadwal-kbm.for-teacher', 'Jadwal Mengajar', 'ri-time-line', 'warning', ['teacherId' => $userId]);
        $addLink(! $isTahfidzDashboard, 'user.kurikulum.realisasi.index', 'Jurnal Pembelajaran', 'ri-article-line', 'secondary');
        $addLink(! $isTahfidzDashboard, 'user.bank-soal.index', 'Bank Soal', 'ri-file-edit-line', 'purple');
        $addLink($isTahfidzDashboard, 'user.tahfidz.setoran.create', 'Input Setoran', 'ri-add-box-line', 'success');
        $addLink($isTahfidzDashboard, 'user.tahfidz.monitoring.index', 'Monitoring Tahfidz', 'ri-line-chart-line', 'warning');
        $addLink($isWaliDashboard, 'user.absensi.harian.index', 'Absensi Kelas', 'ri-calendar-check-line', 'secondary');
        $addLink($isWaliDashboard, 'user.students.index', 'Data Santri Kelas', 'ri-group-line', 'info');
    } else {
        $addLink($isStrukturalDashboard || $isTuDashboard, 'user.teacher-qr.waka-dashboard', 'Kehadiran Guru', 'ri-live-line', 'primary');
        $addLink($isStrukturalDashboard || $isTuDashboard, 'user.approvals.index', 'Approval Center', 'ri-check-double-line', 'warning');
        $addLink($isStrukturalDashboard || $isTuDashboard || $isWaliDashboard, 'user.students.index', 'Data Santri', 'ri-group-line', 'success');
        $addLink($isStrukturalDashboard || $isTuDashboard, 'user.gtk.indexguru', 'Data GTK', 'ri-team-line', 'info');
        $addLink($isSupervisorDashboard, 'waka.supervisi.index', 'Supervisi', 'ri-file-excel-2-line', 'purple');
        $addLink($isStrukturalDashboard || $isWaliDashboard, 'user.absensi.harian.index', 'Absensi Peserta Didik', 'ri-calendar-check-line', 'secondary');
        $addLink($isWaliDashboard, 'user.study-groups.index', 'Rombel & Santri', 'ri-layout-grid-line', 'primary');
    }

    if (! $isGuruDashboard && ! $isStrukturalDashboard && ! $isTuDashboard && ! $isWaliDashboard) {
        $addLink(true, 'user.teacher-qr.scan', 'Scan QR Kehadiran', 'ri-qr-scan-2-line', 'primary');
        $addLink(true, 'user.schools.guru-mapel.index', 'Buku Nilai Saya', 'ri-book-3-line', 'success');
        $addLink(true, 'user.jadwal-kbm.for-teacher', 'Jadwal Mengajar', 'ri-time-line', 'warning', ['teacherId' => $userId]);
        $addLink(true, 'user.kurikulum.realisasi.index', 'Jurnal Pembelajaran', 'ri-article-line', 'secondary');
    }

    $addLink(true, 'user.notifications.page', 'Notifikasi', 'ri-notification-3-line', 'secondary');
    $addLink(true, 'user.profile.my', 'Profil Saya', 'ri-user-line', 'info');

    $links = array_slice($links, 0, 8);
@endphp

@if(! empty($links))
    <section class="dash-section">
        <div class="dash-section-header">
            <span class="dash-section-icon"><i class="ri-flashlight-line"></i></span>
            <h6 class="dash-section-title">Akses Harian</h6>
            <span class="dash-section-line"></span>
            <span class="dash-section-count">Paling sering dipakai</span>
        </div>

        <div class="row g-2">
            @foreach($links as $link)
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ $link['url'] }}"
                       class="card h-100 text-decoration-none border-0 shadow-sm quick-access-card">
                        <div class="card-body py-3 d-flex align-items-center gap-2">
                            <span class="quick-access-icon bg-{{ $link['color'] }}-subtle text-{{ $link['color'] }}">
                                <i class="{{ $link['icon'] }}"></i>
                            </span>
                            <span class="fw-semibold text-body small flex-grow-1">{{ $link['label'] }}</span>
                            <i class="ri-arrow-right-s-line text-muted"></i>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
