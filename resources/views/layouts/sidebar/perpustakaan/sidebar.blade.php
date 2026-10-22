@php
    $showAll = $isSystemAdmin ?? false;
    $pustakaUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
    $isKoordinator = $hasJabatan('Koordinator Perpustakaan') || $hasJabatan('Kepala Perpustakaan');
    $isStaf = $hasJabatan('Staf Perpustakaan');
    $isStudentOperator = $hasRole('Operator Perpustakaan Santri');
    $isPustaka = $showAll || $isKoordinator || $isStaf || $isStudentOperator;
@endphp

<li class="menu-title"><span>Utama</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}" href="{{ route('root') }}">
        <i class="ri-home-6-line"></i><span>Beranda</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}" href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}" href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}" href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>
@if($showAll || $isKoordinator)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}" href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
            <i class="ri-check-double-line"></i><span>Approval Center</span>
        </a>
    </li>
@endif

@if($isPustaka && menu_allowed('perpustakaan'))
    @if($isStudentOperator)
        <li class="menu-title"><span>Operator Santri</span></li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.index']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.index') }}">
                <i class="ri-dashboard-3-line"></i><span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.loans.index') }}">
                <i class="ri-exchange-line"></i><span>Peminjaman &amp; Pengembalian</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.visits.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.visits.index') }}">
                <i class="ri-footprint-line"></i><span>Kunjungan Harian</span>
            </a>
        </li>
    @else
    <li class="menu-title"><span>Dashboard</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.index']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.index') }}">
            <i class="ri-book-2-line"></i><span>Dashboard Perpustakaan</span>
        </a>
    </li>

    @if($showAll || $isKoordinator)
        <li class="menu-title"><span>Master Koleksi</span></li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.catalog.index') }}">
                <i class="ri-book-mark-line"></i><span>Katalog Buku</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.imports.catalog']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.imports.catalog.form') }}">
                <i class="ri-upload-cloud-2-line"></i><span>Import Katalog</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.imports.genres']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.imports.genres.form') }}">
                <i class="ri-upload-cloud-2-line"></i><span>Import Genre</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.genres.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.genres.index') }}">
                <i class="ri-price-tag-3-line"></i><span>Kategori &amp; Klasifikasi</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.racks.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.racks.index') }}">
                <i class="ri-layout-grid-line"></i><span>Manajemen Rak</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.']) && request('collection') === 'reference' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.catalog.index', ['collection' => 'reference']) }}">
                <i class="ri-book-3-line"></i><span>Kitab &amp; Referensi</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.units.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.units.index') }}">
                <i class="ri-settings-4-line"></i><span>Aturan Peminjaman</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.operators.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.operators.index') }}">
                <i class="ri-user-star-line"></i><span>Petugas Santri</span>
            </a>
        </li>
    @endif

    <li class="menu-title"><span>Sirkulasi</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'borrow' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'borrow']) }}">
            <i class="ri-book-open-line"></i><span>Transaksi Peminjaman</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'return' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'return']) }}">
            <i class="ri-arrow-go-back-line"></i><span>Transaksi Pengembalian</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'renew' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'renew']) }}">
            <i class="ri-refresh-line"></i><span>Perpanjangan</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'overdue' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'overdue']) }}">
            <i class="ri-timer-line"></i><span>Buku Terlambat</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.loans.']) && request('scope') === 'history' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.loans.index', ['scope' => 'history']) }}">
            <i class="ri-history-line"></i><span>Riwayat Sirkulasi</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.rooms.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.rooms.index') }}">
            <i class="ri-calendar-event-line"></i><span>Ruang &amp; Agenda</span>
        </a>
    </li>

    <li class="menu-title"><span>Keanggotaan</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.']) && request('member_type') === 'student' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.members.index', ['member_type' => 'student']) }}">
            <i class="ri-user-star-line"></i><span>Anggota Santri</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.']) && request('member_type') === 'gtk' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.members.index', ['member_type' => 'gtk']) }}">
            <i class="ri-team-line"></i><span>Anggota GTK</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.members.']) && ! request('member_type') ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.members.index') }}">
            <i class="ri-id-card-line"></i><span>Kartu Anggota</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.imports.members']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.imports.members.form') }}">
            <i class="ri-user-add-line"></i><span>Import Anggota</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.visits.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.visits.index') }}">
            <i class="ri-footprint-line"></i><span>Kunjungan Harian</span>
        </a>
    </li>

    <li class="menu-title"><span>Denda &amp; Bebas Pustaka</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) && request('tab') !== 'free' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.fines.index') }}">
            <i class="ri-money-dollar-circle-line"></i><span>Daftar Denda</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) && request('status') === 'unpaid' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.fines.index', ['status' => 'unpaid']) }}">
            <i class="ri-hand-coin-line"></i><span>Pembayaran Denda</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.fines.']) && request('tab') === 'free' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.fines.index', ['tab' => 'free']) }}">
            <i class="ri-file-paper-2-line"></i><span>Surat Bebas Pustaka</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link" href="{{ $pustakaUrl('user.perpustakaan.fines.index', ['tab' => 'free']) }}">
            <i class="ri-file-list-3-line"></i><span>Bebas Pustaka Massal</span>
        </a>
    </li>

    @if($showAll || $isKoordinator)
        <li class="menu-title"><span>Inventaris &amp; Pengadaan</span></li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.stock-opnames.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.stock-opnames.index') }}">
                <i class="ri-file-list-2-line"></i><span>Stock Opname</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.catalog.']) && request('condition') === 'damaged' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.catalog.index', ['condition' => 'problem']) }}">
                <i class="ri-book-2-line"></i><span>Buku Rusak / Hilang</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'procurement' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'procurement']) }}">
                <i class="ri-shopping-bag-3-line"></i><span>Pengadaan Buku</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'donation' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'donation']) }}">
                <i class="ri-gift-line"></i><span>Sumbangan Buku</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'weeding' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'weeding']) }}">
                <i class="ri-delete-bin-6-line"></i><span>Penyusutan Koleksi</span>
            </a>
        </li>
    @endif

    <li class="menu-title"><span>Literasi</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request()->route('type') === 'literacy' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy']) }}">
            <i class="ri-book-read-line"></i><span>Pojok Baca</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request('category') === 'Resensi Buku' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy', 'category' => 'Resensi Buku']) }}">
            <i class="ri-edit-2-line"></i><span>Resensi Buku</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request('category') === 'Program Literasi' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy', 'category' => 'Program Literasi']) }}">
            <i class="ri-lightbulb-line"></i><span>Program Literasi</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.programs.']) && request('category') === 'Lomba Literasi' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.programs.index', ['type' => 'literacy', 'category' => 'Lomba Literasi']) }}">
            <i class="ri-trophy-line"></i><span>Lomba Literasi</span>
        </a>
    </li>

    <li class="menu-title"><span>Laporan</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'loans' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'loans']) }}">
            <i class="ri-bar-chart-2-line"></i><span>Statistik Peminjaman</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'popular' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'popular']) }}">
            <i class="ri-fire-line"></i><span>Buku Terpopuler</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'members' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'members']) }}">
            <i class="ri-user-star-line"></i><span>Anggota Teraktif</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.perpustakaan.reports.']) && request('tab') === 'annual' ? ' active' : '' }}" href="{{ $pustakaUrl('user.perpustakaan.reports.index', ['tab' => 'annual']) }}">
            <i class="ri-file-chart-line"></i><span>Laporan Tahunan</span>
        </a>
    </li>

    <li class="menu-title"><span>Referensi</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}" href="{{ $pustakaUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>
    @endif
@endif
